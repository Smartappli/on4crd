<?php
declare(strict_types=1);

$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

$locale = current_locale();
$i18n = [
    'fr' => ['err_incomplete' => 'Informations de réinitialisation incomplètes.', 'err_auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'ok_updated' => 'Mot de passe mis à jour. Vous pouvez vous connecter.', 'err_invalid_link' => 'Le lien est invalide ou expiré.', 'err_reset_disabled' => 'La réinitialisation est désactivée pour ce compte.', 'err_invalid_password' => 'Mot de passe invalide (minimum 8 caractères recommandés).', 'err_too_many' => 'Trop de tentatives. Réessayez plus tard.', 'title' => 'Réinitialiser le mot de passe', 'new_password' => 'Nouveau mot de passe', 'submit' => 'Mettre à jour', 'back_login' => 'Retour à la connexion', 'layout_title' => 'Réinitialisation du mot de passe'],
    'en' => ['err_incomplete' => 'Incomplete reset information.', 'err_auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'ok_updated' => 'Password updated. You can now sign in.', 'err_invalid_link' => 'The link is invalid or expired.', 'err_reset_disabled' => 'Password reset is disabled for this account.', 'err_invalid_password' => 'Invalid password (minimum 8 characters recommended).', 'err_too_many' => 'Too many attempts. Try again later.', 'title' => 'Reset password', 'new_password' => 'New password', 'submit' => 'Update', 'back_login' => 'Back to login', 'layout_title' => 'Password reset'],
    'de' => ['err_incomplete' => 'Unvollständige Informationen zur Zurücksetzung.', 'err_auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'ok_updated' => 'Passwort aktualisiert. Sie können sich jetzt anmelden.', 'err_invalid_link' => 'Der Link ist ungültig oder abgelaufen.', 'err_reset_disabled' => 'Die Zurücksetzung ist für dieses Konto deaktiviert.', 'err_invalid_password' => 'Ungültiges Passwort (mindestens 8 Zeichen empfohlen).', 'err_too_many' => 'Zu viele Versuche. Bitte später erneut versuchen.', 'title' => 'Passwort zurücksetzen', 'new_password' => 'Neues Passwort', 'submit' => 'Aktualisieren', 'back_login' => 'Zurück zur Anmeldung', 'layout_title' => 'Passwortzurücksetzung'],
    'es' => ['err_incomplete' => 'Información de restablecimiento incompleta.', 'err_auth_unavailable' => 'Módulo de autenticación no disponible. Ejecute composer install.', 'ok_updated' => 'Contraseña actualizada. Ya puede iniciar sesión.', 'err_invalid_link' => 'El enlace es inválido o ha caducado.', 'err_reset_disabled' => 'El restablecimiento está desactivado para esta cuenta.', 'err_invalid_password' => 'Contraseña no válida (se recomiendan al menos 8 caracteres).', 'err_too_many' => 'Demasiados intentos. Inténtelo más tarde.', 'title' => 'Restablecer contraseña', 'new_password' => 'Nueva contraseña', 'submit' => 'Actualizar', 'back_login' => 'Volver al inicio de sesión', 'layout_title' => 'Restablecimiento de contraseña'],
    'it' => ['err_incomplete' => 'Informazioni di reset incomplete.', 'err_auth_unavailable' => 'Modulo di autenticazione non disponibile. Esegui composer install.', 'ok_updated' => 'Password aggiornata. Ora puoi accedere.', 'err_invalid_link' => 'Il link non è valido o è scaduto.', 'err_reset_disabled' => 'Il reset password è disattivato per questo account.', 'err_invalid_password' => 'Password non valida (consigliati almeno 8 caratteri).', 'err_too_many' => 'Troppi tentativi. Riprova più tardi.', 'title' => 'Reimposta password', 'new_password' => 'Nuova password', 'submit' => 'Aggiorna', 'back_login' => 'Torna al login', 'layout_title' => 'Reimpostazione password'],
    'pt' => ['err_incomplete' => 'Informações de redefinição incompletas.', 'err_auth_unavailable' => 'Módulo de autenticação indisponível. Execute composer install.', 'ok_updated' => 'Palavra-passe atualizada. Já pode iniciar sessão.', 'err_invalid_link' => 'A ligação é inválida ou expirou.', 'err_reset_disabled' => 'A redefinição está desativada para esta conta.', 'err_invalid_password' => 'Palavra-passe inválida (recomendado mínimo de 8 caracteres).', 'err_too_many' => 'Demasiadas tentativas. Tente novamente mais tarde.', 'title' => 'Redefinir palavra-passe', 'new_password' => 'Nova palavra-passe', 'submit' => 'Atualizar', 'back_login' => 'Voltar ao login', 'layout_title' => 'Redefinição de palavra-passe'],
    'nl' => ['err_incomplete' => 'Onvolledige resetinformatie.', 'err_auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'ok_updated' => 'Wachtwoord bijgewerkt. Je kunt nu inloggen.', 'err_invalid_link' => 'De link is ongeldig of verlopen.', 'err_reset_disabled' => 'Resetten is uitgeschakeld voor dit account.', 'err_invalid_password' => 'Ongeldig wachtwoord (minimaal 8 tekens aanbevolen).', 'err_too_many' => 'Te veel pogingen. Probeer het later opnieuw.', 'title' => 'Wachtwoord resetten', 'new_password' => 'Nieuw wachtwoord', 'submit' => 'Bijwerken', 'back_login' => 'Terug naar inloggen', 'layout_title' => 'Wachtwoord resetten'],
    'ar' => ['err_incomplete' => 'معلومات إعادة التعيين غير مكتملة.', 'err_auth_unavailable' => 'وحدة المصادقة غير متاحة. شغّل composer install.', 'ok_updated' => 'تم تحديث كلمة المرور. يمكنك تسجيل الدخول الآن.', 'err_invalid_link' => 'الرابط غير صالح أو منتهي الصلاحية.', 'err_reset_disabled' => 'إعادة التعيين معطلة لهذا الحساب.', 'err_invalid_password' => 'كلمة مرور غير صالحة (يوصى بحد أدنى 8 أحرف).', 'err_too_many' => 'محاولات كثيرة جدًا. حاول لاحقًا.', 'title' => 'إعادة تعيين كلمة المرور', 'new_password' => 'كلمة المرور الجديدة', 'submit' => 'تحديث', 'back_login' => 'العودة إلى تسجيل الدخول', 'layout_title' => 'إعادة تعيين كلمة المرور'],
    'hi' => ['err_incomplete' => 'रीसेट जानकारी अधूरी है।', 'err_auth_unavailable' => 'प्रमाणीकरण मॉड्यूल उपलब्ध नहीं है। composer install चलाएँ।', 'ok_updated' => 'पासवर्ड अपडेट हो गया। अब आप लॉगिन कर सकते हैं।', 'err_invalid_link' => 'लिंक अमान्य है या इसकी समय-सीमा समाप्त हो गई है।', 'err_reset_disabled' => 'इस खाते के लिए रीसेट अक्षम है।', 'err_invalid_password' => 'अमान्य पासवर्ड (कम से कम 8 अक्षर अनुशंसित)।', 'err_too_many' => 'बहुत अधिक प्रयास। बाद में पुनः प्रयास करें।', 'title' => 'पासवर्ड रीसेट करें', 'new_password' => 'नया पासवर्ड', 'submit' => 'अपडेट करें', 'back_login' => 'लॉगिन पर वापस जाएँ', 'layout_title' => 'पासवर्ड रीसेट'],
    'ja' => ['err_incomplete' => 'リセット情報が不完全です。', 'err_auth_unavailable' => '認証モジュールが利用できません。composer install を実行してください。', 'ok_updated' => 'パスワードを更新しました。ログインできます。', 'err_invalid_link' => 'リンクが無効か期限切れです。', 'err_reset_disabled' => 'このアカウントではリセットが無効です。', 'err_invalid_password' => '無効なパスワードです（8文字以上推奨）。', 'err_too_many' => '試行回数が多すぎます。後でもう一度お試しください。', 'title' => 'パスワードをリセット', 'new_password' => '新しいパスワード', 'submit' => '更新', 'back_login' => 'ログインに戻る', 'layout_title' => 'パスワードリセット'],
    'zh' => ['err_incomplete' => '重置信息不完整。', 'err_auth_unavailable' => '认证模块不可用。请运行 composer install。', 'ok_updated' => '密码已更新。您现在可以登录。', 'err_invalid_link' => '链接无效或已过期。', 'err_reset_disabled' => '该账号已禁用重置功能。', 'err_invalid_password' => '密码无效（建议至少 8 个字符）。', 'err_too_many' => '尝试次数过多，请稍后再试。', 'title' => '重置密码', 'new_password' => '新密码', 'submit' => '更新', 'back_login' => '返回登录', 'layout_title' => '密码重置'],
    'bn' => ['err_incomplete' => 'রিসেট তথ্য অসম্পূর্ণ।', 'err_auth_unavailable' => 'অথেন্টিকেশন মডিউল উপলব্ধ নয়। composer install চালান।', 'ok_updated' => 'পাসওয়ার্ড আপডেট হয়েছে। এখন আপনি লগইন করতে পারবেন।', 'err_invalid_link' => 'লিংকটি অবৈধ বা মেয়াদোত্তীর্ণ।', 'err_reset_disabled' => 'এই অ্যাকাউন্টের জন্য রিসেট নিষ্ক্রিয়।', 'err_invalid_password' => 'অবৈধ পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর সুপারিশকৃত)।', 'err_too_many' => 'অতিরিক্ত প্রচেষ্টা। পরে আবার চেষ্টা করুন।', 'title' => 'পাসওয়ার্ড রিসেট', 'new_password' => 'নতুন পাসওয়ার্ড', 'submit' => 'আপডেট করুন', 'back_login' => 'লগইনে ফিরে যান', 'layout_title' => 'পাসওয়ার্ড রিসেট'],
    'ru' => ['err_incomplete' => 'Недостаточно данных для сброса.', 'err_auth_unavailable' => 'Модуль аутентификации недоступен. Выполните composer install.', 'ok_updated' => 'Пароль обновлён. Теперь вы можете войти.', 'err_invalid_link' => 'Ссылка недействительна или истекла.', 'err_reset_disabled' => 'Сброс пароля для этого аккаунта отключён.', 'err_invalid_password' => 'Недопустимый пароль (рекомендуется минимум 8 символов).', 'err_too_many' => 'Слишком много попыток. Попробуйте позже.', 'title' => 'Сброс пароля', 'new_password' => 'Новый пароль', 'submit' => 'Обновить', 'back_login' => 'Вернуться ко входу', 'layout_title' => 'Сброс пароля'],
    'id' => ['err_incomplete' => 'Informasi reset tidak lengkap.', 'err_auth_unavailable' => 'Modul autentikasi tidak tersedia. Jalankan composer install.', 'ok_updated' => 'Kata sandi diperbarui. Anda sekarang dapat masuk.', 'err_invalid_link' => 'Tautan tidak valid atau kedaluwarsa.', 'err_reset_disabled' => 'Reset dinonaktifkan untuk akun ini.', 'err_invalid_password' => 'Kata sandi tidak valid (disarankan minimal 8 karakter).', 'err_too_many' => 'Terlalu banyak percobaan. Coba lagi nanti.', 'title' => 'Reset kata sandi', 'new_password' => 'Kata sandi baru', 'submit' => 'Perbarui', 'back_login' => 'Kembali ke login', 'layout_title' => 'Reset kata sandi'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $newPassword = (string) ($_POST['password'] ?? '');
        if ($selector === '' || $token === '' || $newPassword === '') {
            throw new RuntimeException($t('err_incomplete'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('err_auth_unavailable'));
        }

        $authClient->resetPassword($selector, $token, $newPassword);
        unset($_SESSION['password_reset_pending']);
        set_flash('success', $t('ok_updated'));
        redirect('login');
    } catch (\Delight\Auth\InvalidSelectorTokenPairException|\Delight\Auth\TokenExpiredException $exception) {
        set_flash('error', $t('err_invalid_link'));
        redirect('forgot_password');
    } catch (\Delight\Auth\ResetDisabledException $exception) {
        set_flash('error', $t('err_reset_disabled'));
        redirect('forgot_password');
    } catch (\Delight\Auth\InvalidPasswordException $exception) {
        set_flash('error', $t('err_invalid_password'));
        redirect_url(route_url('reset_password', ['selector' => $selector, 'token' => $token]));
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', $t('err_too_many'));
        redirect('forgot_password');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('forgot_password');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<input type="hidden" name="selector" value="' . e($selector) . '">'
    . '<input type="hidden" name="token" value="' . e($token) . '">'
    . '<label>' . e($t('new_password')) . '<input type="password" name="password" minlength="8" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">' . e($t('back_login')) . '</a></p>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
