<?php
declare(strict_types=1);

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($callsign === '' || $password === '') {
            throw new RuntimeException('Identifiants requis.');
        }
        if (!table_exists('members')) {
            throw new RuntimeException('La base membres n\'est pas initialisée.');
        }

        $stmt = db()->prepare('SELECT id, password_hash, is_active FROM members WHERE callsign = ? LIMIT 1');
        $stmt->execute([$callsign]);
        $member = $stmt->fetch();

        if (!is_array($member) || (int) ($member['is_active'] ?? 0) !== 1 || !password_verify($password, (string) ($member['password_hash'] ?? ''))) {
            throw new RuntimeException('Indicatif ou mot de passe invalide.');
        }

        $_SESSION['member_id'] = (int) $member['id'];
        set_flash('success', 'Connexion réussie.');
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('login');
    }
}

$content = '<div class="card"><h1>Connexion</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>Indicatif<input type="text" name="callsign" required></label>'
    . '<label>Mot de passe<input type="password" name="password" required></label>'
    . '<button class="button">Se connecter</button></form></div>';

echo render_layout($content, 'Connexion');
