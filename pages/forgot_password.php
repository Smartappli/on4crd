<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['err_email_required' => 'Veuillez renseigner votre email.', 'err_auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'ok_sent' => 'Si un compte existe pour cet email, un lien de réinitialisation a été généré.', 'err_invalid_email' => 'Email invalide.', 'err_not_verified' => 'Compte non vérifié.', 'err_reset_disabled' => 'La réinitialisation est désactivée pour ce compte.', 'err_too_many' => 'Trop de demandes. Réessayez plus tard.', 'title' => 'Mot de passe oublié', 'submit' => 'Envoyer le lien', 'back_login' => 'Retour à la connexion', 'email_label' => 'Email'],
    'en' => ['err_email_required' => 'Please enter your email address.', 'err_auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'ok_sent' => 'If an account exists for this email, a reset link has been generated.', 'err_invalid_email' => 'Invalid email address.', 'err_not_verified' => 'Account not verified.', 'err_reset_disabled' => 'Password reset is disabled for this account.', 'err_too_many' => 'Too many requests. Try again later.', 'title' => 'Forgot password', 'submit' => 'Send link', 'back_login' => 'Back to login', 'email_label' => 'Email'],
    'de' => ['err_email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.', 'err_auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'ok_sent' => 'Wenn ein Konto für diese E-Mail existiert, wurde ein Zurücksetzungslink erstellt.', 'err_invalid_email' => 'Ungültige E-Mail-Adresse.', 'err_not_verified' => 'Konto nicht verifiziert.', 'err_reset_disabled' => 'Die Zurücksetzung ist für dieses Konto deaktiviert.', 'err_too_many' => 'Zu viele Anfragen. Bitte später erneut versuchen.', 'title' => 'Passwort vergessen', 'submit' => 'Link senden', 'back_login' => 'Zurück zur Anmeldung', 'email_label' => 'E-Mail'],
    'es' => ['err_email_required' => 'Introduzca su correo electrónico.', 'err_auth_unavailable' => 'Módulo de autenticación no disponible. Ejecute composer install.', 'ok_sent' => 'Si existe una cuenta para este correo, se ha generado un enlace de restablecimiento.', 'err_invalid_email' => 'Correo electrónico no válido.', 'err_not_verified' => 'Cuenta no verificada.', 'err_reset_disabled' => 'El restablecimiento está desactivado para esta cuenta.', 'err_too_many' => 'Demasiadas solicitudes. Inténtelo más tarde.', 'title' => 'Olvidé mi contraseña', 'submit' => 'Enviar enlace', 'back_login' => 'Volver al inicio de sesión', 'email_label' => 'Correo electrónico'],
    'it' => ['err_email_required' => 'Inserisci il tuo indirizzo email.', 'err_auth_unavailable' => 'Modulo di autenticazione non disponibile. Esegui composer install.', 'ok_sent' => 'Se esiste un account per questa email, è stato generato un link di reset.', 'err_invalid_email' => 'Indirizzo email non valido.', 'err_not_verified' => 'Account non verificato.', 'err_reset_disabled' => 'Il reset password è disattivato per questo account.', 'err_too_many' => 'Troppe richieste. Riprova più tardi.', 'title' => 'Password dimenticata', 'submit' => 'Invia link', 'back_login' => 'Torna al login', 'email_label' => 'Email'],
    'pt' => ['err_email_required' => 'Indique o seu email.', 'err_auth_unavailable' => 'Módulo de autenticação indisponível. Execute composer install.', 'ok_sent' => 'Se existir uma conta para este email, foi gerada uma ligação de redefinição.', 'err_invalid_email' => 'Email inválido.', 'err_not_verified' => 'Conta não verificada.', 'err_reset_disabled' => 'A redefinição está desativada para esta conta.', 'err_too_many' => 'Demasiados pedidos. Tente novamente mais tarde.', 'title' => 'Esqueci-me da palavra-passe', 'submit' => 'Enviar ligação', 'back_login' => 'Voltar ao login', 'email_label' => 'Email'],
    'nl' => ['err_email_required' => 'Vul je e-mailadres in.', 'err_auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'ok_sent' => 'Als er een account bestaat voor dit e-mailadres, is een resetlink gegenereerd.', 'err_invalid_email' => 'Ongeldig e-mailadres.', 'err_not_verified' => 'Account niet geverifieerd.', 'err_reset_disabled' => 'Resetten is uitgeschakeld voor dit account.', 'err_too_many' => 'Te veel aanvragen. Probeer het later opnieuw.', 'title' => 'Wachtwoord vergeten', 'submit' => 'Link verzenden', 'back_login' => 'Terug naar inloggen', 'email_label' => 'E-mail'],
    'ar' => ['err_email_required' => 'يرجى إدخال بريدك الإلكتروني.', 'err_auth_unavailable' => 'وحدة المصادقة غير متاحة. شغّل composer install.', 'ok_sent' => 'إذا كان هناك حساب مرتبط بهذا البريد، فقد تم إنشاء رابط إعادة التعيين.', 'err_invalid_email' => 'بريد إلكتروني غير صالح.', 'err_not_verified' => 'الحساب غير مُتحقّق منه.', 'err_reset_disabled' => 'إعادة التعيين معطلة لهذا الحساب.', 'err_too_many' => 'طلبات كثيرة جدًا. حاول لاحقًا.', 'title' => 'نسيت كلمة المرور', 'submit' => 'إرسال الرابط', 'back_login' => 'العودة إلى تسجيل الدخول', 'email_label' => 'البريد الإلكتروني'],
    'hi' => ['err_email_required' => 'कृपया अपना ईमेल दर्ज करें।', 'err_auth_unavailable' => 'प्रमाणीकरण मॉड्यूल उपलब्ध नहीं है। composer install चलाएँ।', 'ok_sent' => 'यदि इस ईमेल के लिए खाता मौजूद है, तो रीसेट लिंक बना दिया गया है।', 'err_invalid_email' => 'अमान्य ईमेल पता।', 'err_not_verified' => 'खाता सत्यापित नहीं है।', 'err_reset_disabled' => 'इस खाते के लिए रीसेट अक्षम है।', 'err_too_many' => 'बहुत अधिक अनुरोध। बाद में पुनः प्रयास करें।', 'title' => 'पासवर्ड भूल गए', 'submit' => 'लिंक भेजें', 'back_login' => 'लॉगिन पर वापस जाएँ', 'email_label' => 'ईमेल'],
    'ja' => ['err_email_required' => 'メールアドレスを入力してください。', 'err_auth_unavailable' => '認証モジュールが利用できません。composer install を実行してください。', 'ok_sent' => 'このメールのアカウントが存在する場合、リセットリンクが生成されました。', 'err_invalid_email' => '無効なメールアドレスです。', 'err_not_verified' => 'アカウントが未確認です。', 'err_reset_disabled' => 'このアカウントではリセットが無効です。', 'err_too_many' => 'リクエストが多すぎます。後でもう一度お試しください。', 'title' => 'パスワードを忘れた場合', 'submit' => 'リンクを送信', 'back_login' => 'ログインに戻る', 'email_label' => 'メールアドレス'],
    'zh' => ['err_email_required' => '请输入您的邮箱。', 'err_auth_unavailable' => '认证模块不可用。请运行 composer install。', 'ok_sent' => '如果该邮箱对应账号存在，重置链接已生成。', 'err_invalid_email' => '邮箱地址无效。', 'err_not_verified' => '账号未验证。', 'err_reset_disabled' => '该账号已禁用重置功能。', 'err_too_many' => '请求过多，请稍后重试。', 'title' => '忘记密码', 'submit' => '发送链接', 'back_login' => '返回登录', 'email_label' => '邮箱'],
    'bn' => ['err_email_required' => 'অনুগ্রহ করে আপনার ইমেইল দিন।', 'err_auth_unavailable' => 'অথেন্টিকেশন মডিউল উপলব্ধ নয়। composer install চালান।', 'ok_sent' => 'এই ইমেইলের জন্য অ্যাকাউন্ট থাকলে একটি রিসেট লিংক তৈরি করা হয়েছে।', 'err_invalid_email' => 'অবৈধ ইমেইল ঠিকানা।', 'err_not_verified' => 'অ্যাকাউন্ট যাচাই করা হয়নি।', 'err_reset_disabled' => 'এই অ্যাকাউন্টের জন্য রিসেট নিষ্ক্রিয়।', 'err_too_many' => 'অতিরিক্ত অনুরোধ। পরে আবার চেষ্টা করুন।', 'title' => 'পাসওয়ার্ড ভুলে গেছেন', 'submit' => 'লিংক পাঠান', 'back_login' => 'লগইনে ফিরে যান', 'email_label' => 'ইমেইল'],
    'ru' => ['err_email_required' => 'Пожалуйста, укажите ваш email.', 'err_auth_unavailable' => 'Модуль аутентификации недоступен. Выполните composer install.', 'ok_sent' => 'Если для этого email существует аккаунт, ссылка для сброса создана.', 'err_invalid_email' => 'Неверный email.', 'err_not_verified' => 'Аккаунт не подтверждён.', 'err_reset_disabled' => 'Сброс пароля для этого аккаунта отключён.', 'err_too_many' => 'Слишком много запросов. Попробуйте позже.', 'title' => 'Забыли пароль', 'submit' => 'Отправить ссылку', 'back_login' => 'Вернуться ко входу', 'email_label' => 'Email'],
    'id' => ['err_email_required' => 'Silakan masukkan email Anda.', 'err_auth_unavailable' => 'Modul autentikasi tidak tersedia. Jalankan composer install.', 'ok_sent' => 'Jika akun untuk email ini ada, tautan reset telah dibuat.', 'err_invalid_email' => 'Alamat email tidak valid.', 'err_not_verified' => 'Akun belum diverifikasi.', 'err_reset_disabled' => 'Reset dinonaktifkan untuk akun ini.', 'err_too_many' => 'Terlalu banyak permintaan. Coba lagi nanti.', 'title' => 'Lupa kata sandi', 'submit' => 'Kirim tautan', 'back_login' => 'Kembali ke login', 'email_label' => 'Email'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException($t('err_email_required'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('err_auth_unavailable'));
        }

        $authClient->forgotPassword($email, static function (string $selector, string $token): void {
            // Intégration e-mail à brancher ici (SMTP/API) pour transmettre le lien de reset.
            // Le callback est obligatoire pour récupérer selector/token.
            $_SESSION['password_reset_pending'] = hash('sha256', $selector . ':' . $token);
        });

        set_flash('success', $t('ok_sent'));
        redirect('forgot_password');
    } catch (\Delight\Auth\InvalidEmailException $exception) {
        set_flash('error', $t('err_invalid_email'));
        redirect('forgot_password');
    } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
        set_flash('error', $t('err_not_verified'));
        redirect('forgot_password');
    } catch (\Delight\Auth\ResetDisabledException $exception) {
        set_flash('error', $t('err_reset_disabled'));
        redirect('forgot_password');
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', $t('err_too_many'));
        redirect('forgot_password');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e($t('email_label')) . '<input type="email" name="email" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">' . e($t('back_login')) . '</a></p>';

$content .= '</div>';

echo render_layout($content, $t('title'));
