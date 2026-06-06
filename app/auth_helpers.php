<?php
declare(strict_types=1);

if (!function_exists('current_user')) {
function mark_authenticated_response_private(): void
{
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}

function auth_bypass_member_id(): int
{
    $environment = strtolower(trim((string) config('app.env', 'production')));
    if ($environment !== 'development') {
        return 0;
    }

    $configuredBypassId = max(0, (int) config('app.auth_bypass_member_id', 0));
    if ($configuredBypassId > 0) {
        return $configuredBypassId;
    }

    $route = (string) ($_GET['route'] ?? 'home');
    $temporaryBypassForMembers = (bool) config('app.bypass_member_modules_auth', false);
    $memberBypassRoutes = [
        'dashboard',
        'save_dashboard',
        'widget_render',
        'profile',
        'qsl',
        'qsl_preview',
        'qsl_export',
        'classifieds',
        'auction_bid',
        'newsletter',
    ];
    if ($temporaryBypassForMembers && in_array($route, $memberBypassRoutes, true) && table_exists('members')) {
        $stmt = db()->query('SELECT id FROM members WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $firstActiveMemberId = $stmt !== false ? (int) $stmt->fetchColumn() : 0;

        return max(0, $firstActiveMemberId);
    }

    $allowDevelopmentBypass = (bool) config('app.disable_login_in_development', false);
    $route = (string) ($_GET['route'] ?? 'home');
    $memberBypassRoutes = [
        'dashboard',
        'save_dashboard',
        'widget_render',
        'profile',
        'qsl',
        'qsl_preview',
        'qsl_export',
        'classifieds',
        'auction_bid',
        'newsletter',
    ];
    if (!$allowDevelopmentBypass || !table_exists('members') || !in_array($route, $memberBypassRoutes, true)) {
        return 0;
    }

    $stmt = db()->query('SELECT id FROM members WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    $firstActiveMemberId = $stmt !== false ? (int) $stmt->fetchColumn() : 0;

    return max(0, $firstActiveMemberId);
}

function bypass_member_user(int $memberId): ?array
{
    if ($memberId <= 0 || !table_exists('members')) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, callsign, full_name, email, locator, is_active, is_committee FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$memberId]);
    $row = $stmt->fetch();
    if (!is_array($row) || (int) ($row['is_active'] ?? 0) !== 1) {
        return null;
    }

    return $row;
}

function authenticated_member_columns(): array
{
    $memberColumns = ['id'];
    foreach (['auth_user_id', 'callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee', 'password_change_required', 'password_reset_forced_at'] as $memberColumn) {
        if (table_has_column('members', $memberColumn)) {
            $memberColumns[] = $memberColumn;
        }
    }

    return array_values(array_unique($memberColumns));
}

function authenticated_member_row(\Delight\Auth\Auth $authClient, int $sessionMemberId = 0): ?array
{
    if (!$authClient->isLoggedIn() || !table_exists('members')) {
        return null;
    }

    $authUserId = (int) $authClient->getUserId();
    if ($authUserId <= 0) {
        return null;
    }

    $memberColumns = authenticated_member_columns();
    $selectColumns = implode(', ', $memberColumns);
    $hasAuthUserIdColumn = table_has_column('members', 'auth_user_id');
    $authUsername = strtoupper(trim((string) $authClient->getUsername()));

    try {
        if ($hasAuthUserIdColumn) {
            $stmt = db()->prepare('SELECT ' . $selectColumns . ' FROM members WHERE auth_user_id = ? LIMIT 1');
            $stmt->execute([$authUserId]);
            $row = $stmt->fetch();
            if (is_array($row) && (int) ($row['is_active'] ?? 0) === 1) {
                return $row;
            }
            if (is_array($row)) {
                return null;
            }
        }

        if ($authUsername !== '') {
            $fallbackStmt = db()->prepare('SELECT ' . $selectColumns . ' FROM members WHERE UPPER(callsign) = ? LIMIT 1');
            $fallbackStmt->execute([$authUsername]);
            $fallbackRow = $fallbackStmt->fetch();
            if (is_array($fallbackRow) && (int) ($fallbackRow['is_active'] ?? 0) === 1) {
                if (!$hasAuthUserIdColumn) {
                    return $fallbackRow;
                }

                $linkedAuthUserId = (int) ($fallbackRow['auth_user_id'] ?? 0);
                if ($linkedAuthUserId === $authUserId) {
                    return $fallbackRow;
                }
                if ($linkedAuthUserId === 0) {
                    $repairStmt = db()->prepare('UPDATE members SET auth_user_id = ? WHERE id = ? AND (auth_user_id IS NULL OR auth_user_id = 0) LIMIT 1');
                    $repairStmt->execute([$authUserId, (int) $fallbackRow['id']]);
                    if ($repairStmt->rowCount() > 0) {
                        $fallbackRow['auth_user_id'] = $authUserId;
                        return $fallbackRow;
                    }
                }
            }
        }

        if (!$hasAuthUserIdColumn && $sessionMemberId > 0) {
            $sessionStmt = db()->prepare('SELECT ' . $selectColumns . ' FROM members WHERE id = ? LIMIT 1');
            $sessionStmt->execute([$sessionMemberId]);
            $sessionRow = $sessionStmt->fetch();
            if (is_array($sessionRow) && (int) ($sessionRow['is_active'] ?? 0) === 1) {
                $sessionCallsign = strtoupper(trim((string) ($sessionRow['callsign'] ?? '')));
                if ($authUsername === '' || hash_equals($authUsername, $sessionCallsign)) {
                    return $sessionRow;
                }
            }
        }
    } catch (Throwable) {
        return null;
    }

    return null;
}

function current_user(): ?array
{
    static $cache = null;
    static $loaded = false;

    if ($loaded) {
        return $cache;
    }
    $loaded = true;

    $memberId = (int) ($_SESSION['member_id'] ?? 0);
    $authClient = auth();
    if ($authClient !== null && $authClient->isLoggedIn()) {
        $row = authenticated_member_row($authClient, $memberId);
        if (!is_array($row)) {
            try {
                $authClient->logOut();
            } catch (Throwable) {
                // Keep the local cleanup below even if the auth library cannot update its tables.
            }
            unset($_SESSION['member_id']);
            $cache = null;
            return null;
        }

        $_SESSION['member_id'] = (int) ($row['id'] ?? 0);
        mark_authenticated_response_private();
        $cache = $row;
        return $cache;
    } elseif ($authClient !== null && $memberId > 0) {
        unset($_SESSION['member_id']);
        $memberId = 0;
    } elseif ($authClient === null && $memberId > 0) {
        unset($_SESSION['member_id']);
        $memberId = 0;
    }

    if ($memberId <= 0) {
        $bypassMemberId = auth_bypass_member_id();
        if ($bypassMemberId > 0) {
            $bypassUser = bypass_member_user($bypassMemberId);
            if (is_array($bypassUser)) {
                $_SESSION['member_id'] = (int) $bypassUser['id'];
                mark_authenticated_response_private();
                $cache = $bypassUser;
                return $cache;
            }
        }

        $cache = null;
        return null;
    }
}
}

if (!function_exists('member_password_change_required')) {
function member_password_change_required(?array $user = null): bool
{
    $user ??= current_user();
    if (!is_array($user)) {
        return false;
    }

    if (array_key_exists('password_reset_forced_at', $user)) {
        return (int) ($user['password_change_required'] ?? 0) === 1
            && trim((string) ($user['password_reset_forced_at'] ?? '')) !== '';
    }

    $memberId = (int) ($user['id'] ?? 0);
    if (
        $memberId <= 0
        || !table_exists('members')
        || !table_has_column('members', 'password_change_required')
        || !table_has_column('members', 'password_reset_forced_at')
    ) {
        return false;
    }

    try {
        $stmt = db()->prepare('SELECT password_change_required, password_reset_forced_at FROM members WHERE id = ? LIMIT 1');
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();

        return is_array($row)
            && (int) ($row['password_change_required'] ?? 0) === 1
            && trim((string) ($row['password_reset_forced_at'] ?? '')) !== '';
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('require_login')) {
function require_login(?string $nextUrl = null): array
{
    $user = current_user();
    if ($user === null) {
        $locale = current_locale();
        $message = match ($locale) {
            'en' => 'Please sign in to continue.',
            'de' => 'Bitte melden Sie sich an, um fortzufahren.',
            'nl' => 'Log in om verder te gaan.',
            'es' => 'Inicia sesión para continuar.',
            'it' => 'Accedi per continuare.',
            'pt' => 'Inicie sessão para continuar.',
            'ar' => 'يرجى تسجيل الدخول للمتابعة.',
            'hi' => 'जारी रखने के लिए कृपया लॉग इन करें।',
            'ja' => '続行するにはログインしてください。',
            'zh' => '请先登录以继续。',
            'bn' => 'চালিয়ে যেতে অনুগ্রহ করে লগইন করুন।',
            'ru' => 'Пожалуйста, войдите, чтобы продолжить.',
            'id' => 'Silakan masuk untuk melanjutkan.',
            default => 'Veuillez vous connecter pour continuer.',
        };
        set_flash('error', $message);
        $loginQuery = [];
        $safeNextUrl = $nextUrl !== null ? safe_login_next_url($nextUrl) : null;
        if ($safeNextUrl !== null) {
            $loginQuery['next'] = $safeNextUrl;
        }
        redirect_url(route_url('login', $loginQuery));
    }

    return $user;
}
}

if (!function_exists('logout_member')) {
function logout_member(): void
{
    $authClient = auth();
    if ($authClient !== null && $authClient->isLoggedIn()) {
        try {
            $authClient->logOut();
        } catch (Throwable) {
            // Continue with local cleanup even if the auth library cannot update its tables.
        }
    }

    foreach ([
        'member_id',
        'auth_logged_in',
        'auth_user_id',
        'auth_email',
        'auth_username',
        'auth_status',
        'auth_roles',
        'auth_remembered',
        'auth_last_resync',
        'auth_force_logout',
        'auth_awaiting_2fa_until',
        'auth_awaiting_2fa_user_id',
        'auth_awaiting_2fa_remember_duration',
    ] as $sessionKey) {
        unset($_SESSION[$sessionKey]);
    }

    $rememberCookieNames = ['auth_remember'];
    if (class_exists(\Delight\Auth\Auth::class)) {
        $rememberCookieNames[] = \Delight\Auth\Auth::createRememberCookieName(session_name());
    }
    $cookieParams = session_get_cookie_params();
    foreach (array_unique($rememberCookieNames) as $cookieName) {
        unset($_COOKIE[$cookieName]);
        $cookieOptions = [
            'expires' => time() - 3600,
            'path' => $cookieParams['path'] ?? '/',
            'secure' => (bool) ($cookieParams['secure'] ?? false),
            'httponly' => true,
            'samesite' => (string) ($cookieParams['samesite'] ?? 'Lax'),
        ];
        if (!empty($cookieParams['domain'])) {
            $cookieOptions['domain'] = (string) $cookieParams['domain'];
        }
        setcookie($cookieName, '', $cookieOptions);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
}
