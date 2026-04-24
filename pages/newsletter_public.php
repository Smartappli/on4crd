<?php
declare(strict_types=1);

newsletter_ensure_tables();

set_page_meta([
    'title' => 'Inscription newsletter',
    'description' => 'Inscrivez-vous à la newsletter ON4CRD.',
]);

$prefillEmail = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = newsletter_normalize_email((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException('Adresse email invalide.');
        }

        if (!newsletter_upsert_subscriber($email, null, 'public_form')) {
            throw new RuntimeException('Adresse email invalide.');
        }

        set_flash('success', 'Votre inscription à la newsletter est confirmée.');
        redirect('newsletter_public');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        $prefillEmail = trim((string) ($_POST['email'] ?? ''));
    }
}

ob_start();
?>
<div class="card">
    <h1>Inscription newsletter</h1>
    <p>Recevez les actualités du Radio Club Durnal directement par email.</p>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label for="newsletter-public-email">Email newsletter</label>
        <input id="newsletter-public-email" type="email" name="email" value="<?= e($prefillEmail) ?>" required>
        <button type="submit" class="button">S'inscrire à la newsletter</button>
    </form>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Inscription newsletter');
