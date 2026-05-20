<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['required' => 'Identifiants requis.', 'captcha_invalid' => 'Captcha invalide.', 'auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'invalid_credentials' => 'Indicatif ou mot de passe invalide.', 'too_many' => 'Trop de tentatives de connexion. Réessayez dans quelques minutes.', 'not_verified' => 'Votre compte n’est pas encore vérifié.', 'login_success' => 'Connexion réussie.', 'title' => 'Connexion', 'callsign' => 'Indicatif', 'password' => 'Mot de passe', 'captcha_question' => 'Captcha : combien font', 'login' => 'Se connecter', 'forgot_password' => 'Mot de passe oublié ?', 'no_member' => 'Pas encore membre ?', 'create_account' => 'Créer un compte'],
    'en' => ['required' => 'Credentials required.', 'captcha_invalid' => 'Invalid captcha.', 'auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'invalid_credentials' => 'Invalid callsign or password.', 'too_many' => 'Too many login attempts. Try again in a few minutes.', 'not_verified' => 'Your account is not verified yet.', 'login_success' => 'Login successful.', 'title' => 'Login', 'callsign' => 'Callsign', 'password' => 'Password', 'captcha_question' => 'Captcha: how much is', 'login' => 'Log in', 'forgot_password' => 'Forgot password?', 'no_member' => 'Not a member yet?', 'create_account' => 'Create an account'],
    'de' => ['required' => 'Anmeldedaten erforderlich.', 'captcha_invalid' => 'Ungültiges Captcha.', 'auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'invalid_credentials' => 'Ungültiges Rufzeichen oder Passwort.', 'too_many' => 'Zu viele Anmeldeversuche. Versuchen Sie es in einigen Minuten erneut.', 'not_verified' => 'Ihr Konto ist noch nicht verifiziert.', 'login_success' => 'Anmeldung erfolgreich.', 'title' => 'Anmeldung', 'callsign' => 'Rufzeichen', 'password' => 'Passwort', 'captcha_question' => 'Captcha: wie viel ist', 'login' => 'Anmelden', 'forgot_password' => 'Passwort vergessen?', 'no_member' => 'Noch kein Mitglied?', 'create_account' => 'Konto erstellen'],
    'es' => ['required' => 'Credenciales obligatorias.', 'captcha_invalid' => 'Captcha no válido.', 'auth_unavailable' => 'Módulo de autenticación no disponible. Ejecute composer install.', 'invalid_credentials' => 'Indicativo o contraseña no válidos.', 'too_many' => 'Demasiados intentos de acceso. Inténtelo de nuevo en unos minutos.', 'not_verified' => 'Su cuenta aún no está verificada.', 'login_success' => 'Inicio de sesión correcto.', 'title' => 'Iniciar sesión', 'callsign' => 'Indicativo', 'password' => 'Contraseña', 'captcha_question' => 'Captcha: cuánto es', 'login' => 'Entrar', 'forgot_password' => '¿Olvidó su contraseña?', 'no_member' => '¿Aún no es miembro?', 'create_account' => 'Crear una cuenta'],
    'it' => ['required' => 'Credenziali obbligatorie.', 'captcha_invalid' => 'Captcha non valido.', 'auth_unavailable' => 'Modulo di autenticazione non disponibile. Esegui composer install.', 'invalid_credentials' => 'Nominativo o password non validi.', 'too_many' => 'Troppi tentativi di accesso. Riprova tra qualche minuto.', 'not_verified' => 'Il tuo account non è ancora verificato.', 'login_success' => 'Accesso effettuato.', 'title' => 'Accedi', 'callsign' => 'Nominativo', 'password' => 'Password', 'captcha_question' => 'Captcha: quanto fa', 'login' => 'Accedi', 'forgot_password' => 'Password dimenticata?', 'no_member' => 'Non sei ancora membro?', 'create_account' => 'Crea un account'],
    'pt' => ['required' => 'Credenciais obrigatórias.', 'captcha_invalid' => 'Captcha inválido.', 'auth_unavailable' => 'Módulo de autenticação indisponível. Execute composer install.', 'invalid_credentials' => 'Indicativo ou palavra-passe inválidos.', 'too_many' => 'Demasiadas tentativas de login. Tente novamente em alguns minutos.', 'not_verified' => 'A sua conta ainda não está verificada.', 'login_success' => 'Sessão iniciada com sucesso.', 'title' => 'Iniciar sessão', 'callsign' => 'Indicativo', 'password' => 'Palavra-passe', 'captcha_question' => 'Captcha: quanto é', 'login' => 'Entrar', 'forgot_password' => 'Esqueceu-se da palavra-passe?', 'no_member' => 'Ainda não é membro?', 'create_account' => 'Criar conta'],
    'nl' => ['required' => 'Inloggegevens vereist.', 'captcha_invalid' => 'Ongeldige captcha.', 'auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'invalid_credentials' => 'Ongeldig roepnaam of wachtwoord.', 'too_many' => 'Te veel inlogpogingen. Probeer het over enkele minuten opnieuw.', 'not_verified' => 'Je account is nog niet geverifieerd.', 'login_success' => 'Succesvol ingelogd.', 'title' => 'Inloggen', 'callsign' => 'Roepnaam', 'password' => 'Wachtwoord', 'captcha_question' => 'Captcha: hoeveel is', 'login' => 'Inloggen', 'forgot_password' => 'Wachtwoord vergeten?', 'no_member' => 'Nog geen lid?', 'create_account' => 'Account aanmaken'],
    'ar' => ['required' => 'بيانات الدخول مطلوبة.', 'captcha_invalid' => 'Captcha غير صالح.', 'auth_unavailable' => 'وحدة المصادقة غير متاحة. شغّل composer install.', 'invalid_credentials' => 'نداء أو كلمة مرور غير صحيحة.', 'too_many' => 'محاولات تسجيل دخول كثيرة جدًا. حاول مجددًا بعد بضع دقائق.', 'not_verified' => 'حسابك غير مُتحقّق منه بعد.', 'login_success' => 'تم تسجيل الدخول بنجاح.', 'title' => 'تسجيل الدخول', 'callsign' => 'نداء', 'password' => 'كلمة المرور', 'captcha_question' => 'Captcha: كم يساوي', 'login' => 'دخول', 'forgot_password' => 'نسيت كلمة المرور؟', 'no_member' => 'لست عضوًا بعد؟', 'create_account' => 'إنشاء حساب'],
    'hi' => ['required' => 'लॉगिन विवरण आवश्यक हैं।', 'captcha_invalid' => 'अमान्य कैप्चा।', 'auth_unavailable' => 'प्रमाणीकरण मॉड्यूल उपलब्ध नहीं है। composer install चलाएँ।', 'invalid_credentials' => 'अमान्य कॉलसाइन या पासवर्ड।', 'too_many' => 'बहुत अधिक लॉगिन प्रयास। कुछ मिनट बाद पुनः प्रयास करें।', 'not_verified' => 'आपका खाता अभी सत्यापित नहीं है।', 'login_success' => 'लॉगिन सफल।', 'title' => 'लॉगिन', 'callsign' => 'कॉलसाइन', 'password' => 'पासवर्ड', 'captcha_question' => 'कैप्चा: कितना होता है', 'login' => 'लॉगिन करें', 'forgot_password' => 'पासवर्ड भूल गए?', 'no_member' => 'अभी सदस्य नहीं हैं?', 'create_account' => 'खाता बनाएँ'],
    'ja' => ['required' => '認証情報が必要です。', 'captcha_invalid' => '無効なCaptchaです。', 'auth_unavailable' => '認証モジュールが利用できません。composer install を実行してください。', 'invalid_credentials' => 'コールサインまたはパスワードが無効です。', 'too_many' => 'ログイン試行が多すぎます。数分後に再試行してください。', 'not_verified' => 'アカウントがまだ確認されていません。', 'login_success' => 'ログインしました。', 'title' => 'ログイン', 'callsign' => 'コールサイン', 'password' => 'パスワード', 'captcha_question' => 'Captcha: 次の計算は', 'login' => 'ログイン', 'forgot_password' => 'パスワードを忘れましたか？', 'no_member' => 'まだメンバーではありませんか？', 'create_account' => 'アカウント作成'],
    'zh' => ['required' => '需要填写登录凭据。', 'captcha_invalid' => 'Captcha 无效。', 'auth_unavailable' => '认证模块不可用。请运行 composer install。', 'invalid_credentials' => '呼号或密码无效。', 'too_many' => '登录尝试次数过多。请几分钟后再试。', 'not_verified' => '您的账号尚未验证。', 'login_success' => '登录成功。', 'title' => '登录', 'callsign' => '呼号', 'password' => '密码', 'captcha_question' => 'Captcha：计算', 'login' => '登录', 'forgot_password' => '忘记密码？', 'no_member' => '还不是会员？', 'create_account' => '创建账号'],
    'bn' => ['required' => 'লগইন তথ্য আবশ্যক।', 'captcha_invalid' => 'অবৈধ ক্যাপচা।', 'auth_unavailable' => 'অথেন্টিকেশন মডিউল উপলব্ধ নয়। composer install চালান।', 'invalid_credentials' => 'অবৈধ কলসাইন বা পাসওয়ার্ড।', 'too_many' => 'অতিরিক্ত লগইন প্রচেষ্টা। কয়েক মিনিট পরে আবার চেষ্টা করুন।', 'not_verified' => 'আপনার অ্যাকাউন্ট এখনও যাচাই হয়নি।', 'login_success' => 'লগইন সফল।', 'title' => 'লগইন', 'callsign' => 'কলসাইন', 'password' => 'পাসওয়ার্ড', 'captcha_question' => 'ক্যাপচা: কত হয়', 'login' => 'লগইন করুন', 'forgot_password' => 'পাসওয়ার্ড ভুলে গেছেন?', 'no_member' => 'এখনও সদস্য নন?', 'create_account' => 'অ্যাকাউন্ট তৈরি করুন'],
    'ru' => ['required' => 'Требуются учетные данные.', 'captcha_invalid' => 'Неверная captcha.', 'auth_unavailable' => 'Модуль аутентификации недоступен. Выполните composer install.', 'invalid_credentials' => 'Неверный позывной или пароль.', 'too_many' => 'Слишком много попыток входа. Повторите через несколько минут.', 'not_verified' => 'Ваш аккаунт ещё не подтверждён.', 'login_success' => 'Вход выполнен успешно.', 'title' => 'Вход', 'callsign' => 'Позывной', 'password' => 'Пароль', 'captcha_question' => 'Captcha: сколько будет', 'login' => 'Войти', 'forgot_password' => 'Забыли пароль?', 'no_member' => 'Ещё не участник?', 'create_account' => 'Создать аккаунт'],
    'id' => ['required' => 'Kredensial wajib diisi.', 'captcha_invalid' => 'Captcha tidak valid.', 'auth_unavailable' => 'Modul autentikasi tidak tersedia. Jalankan composer install.', 'invalid_credentials' => 'Callsign atau kata sandi tidak valid.', 'too_many' => 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.', 'not_verified' => 'Akun Anda belum diverifikasi.', 'login_success' => 'Login berhasil.', 'title' => 'Masuk', 'callsign' => 'Callsign', 'password' => 'Kata sandi', 'captcha_question' => 'Captcha: berapa hasil', 'login' => 'Masuk', 'forgot_password' => 'Lupa kata sandi?', 'no_member' => 'Belum menjadi anggota?', 'create_account' => 'Buat akun'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $captcha = trim((string) ($_POST['captcha'] ?? ''));
        $captchaExpected = (string) ($_SESSION['login_captcha'] ?? '');

        if ($callsign === '' || $password === '' || $captcha === '') {
            throw new RuntimeException((string) $t['required']);
        }
        if (!hash_equals($captchaExpected, $captcha)) {
            throw new RuntimeException((string) $t['captcha_invalid']);
        }
        unset($_SESSION['login_captcha']);
        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException((string) $t['auth_unavailable']);
        }

        try {
            $authClient->loginWithUsername($callsign, $password);
        } catch (\Delight\Auth\UnknownUsernameException|\Delight\Auth\InvalidPasswordException $exception) {
            throw new RuntimeException((string) $t['invalid_credentials']);
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException((string) $t['too_many']);
        } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
            throw new RuntimeException((string) $t['not_verified']);
        }

        $_SESSION['member_id'] = (int) $authClient->getUserId();
        set_flash('success', (string) $t['login_success']);
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('login');
    }
}

$captchaA = random_int(1, 9);
$captchaB = random_int(1, 9);
$captchaExpected = $captchaA + $captchaB;
$_SESSION['login_captcha'] = (string) $captchaExpected;

$content = '<div class="card narrow login-card"><h1>' . e((string) $t['title']) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e((string) $t['callsign']) . '<input type="text" name="callsign" required></label>'
    . '<label>' . e((string) $t['password']) . '<input type="password" name="password" required></label>'
    . '<label>' . e((string) $t['captcha_question']) . ' ' . $captchaA . ' + ' . $captchaB . ' ?'
    . '<input type="text" name="captcha" inputmode="numeric" autocomplete="off" required></label>'
    . '<button class="button">' . e((string) $t['login']) . '</button></form>'
    . '<p><a href="' . e(route_url('forgot_password')) . '">' . e((string) $t['forgot_password']) . '</a></p>'
    . '<p>' . e((string) $t['no_member']) . ' <a href="' . e(route_url('register')) . '">' . e((string) $t['create_account']) . '</a></p>'
    . '</div>';

echo render_layout($content, (string) $t['title']);
