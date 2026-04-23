<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$returnRoute = trim((string) ($_POST['return_route'] ?? 'home'));

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Veuillez compléter le formulaire de contact correctement.');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

$safeName = str_replace(["\r", "\n"], ' ', $name);
$safeEmail = str_replace(["\r", "\n"], '', $email);

$subject = 'Nouveau message de contact footer ON4CRD';
$body = "Nom: {$safeName}\nEmail: {$safeEmail}\n\nMessage:\n{$message}\n";
$headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
    . 'Reply-To: ' . $safeEmail . "\r\n"
    . 'Content-Type: text/plain; charset=UTF-8';

$sent = @mail('on4crd@gmail.com', $subject, $body, $headers);

if ($sent) {
    set_flash('success', 'Votre message a bien été envoyé.');
} else {
    set_flash('error', 'Impossible d\'envoyer le message pour le moment.');
}

redirect($returnRoute !== '' ? $returnRoute : 'home');
