<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['required' => 'Tous les champs sont obligatoires.', 'auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'invalid_data' => 'Les informations fournies sont invalides.', 'already_exists' => 'Un compte existe déjà avec cet email ou cet indicatif.', 'too_many' => 'Trop de tentatives. Merci de réessayer plus tard.', 'ok_created' => 'Compte créé avec succès. Bienvenue dans l’espace membre !', 'title' => 'Créer un compte membre', 'callsign' => 'Indicatif', 'full_name' => 'Nom complet', 'email' => 'Email', 'password' => 'Mot de passe', 'submit' => 'Créer mon compte', 'already_registered' => 'Déjà inscrit ?', 'login' => 'Se connecter', 'layout_title' => 'Inscription'],
    'en' => ['required' => 'All fields are required.', 'auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'invalid_data' => 'Provided information is invalid.', 'already_exists' => 'An account already exists with this email or callsign.', 'too_many' => 'Too many attempts. Please try again later.', 'ok_created' => 'Account created successfully. Welcome to the member area!', 'title' => 'Create a member account', 'callsign' => 'Callsign', 'full_name' => 'Full name', 'email' => 'Email', 'password' => 'Password', 'submit' => 'Create my account', 'already_registered' => 'Already registered?', 'login' => 'Log in', 'layout_title' => 'Register'],
    'de' => ['required' => 'Alle Felder sind erforderlich.', 'auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'invalid_data' => 'Die angegebenen Informationen sind ungültig.', 'already_exists' => 'Ein Konto mit dieser E-Mail oder diesem Rufzeichen existiert bereits.', 'too_many' => 'Zu viele Versuche. Bitte später erneut versuchen.', 'ok_created' => 'Konto erfolgreich erstellt. Willkommen im Mitgliederbereich!', 'title' => 'Mitgliedskonto erstellen', 'callsign' => 'Rufzeichen', 'full_name' => 'Vollständiger Name', 'email' => 'E-Mail', 'password' => 'Passwort', 'submit' => 'Mein Konto erstellen', 'already_registered' => 'Bereits registriert?', 'login' => 'Anmelden', 'layout_title' => 'Registrierung'],
    'es' => ['required' => 'Todos los campos son obligatorios.', 'auth_unavailable' => 'Módulo de autenticación no disponible. Ejecute composer install.', 'invalid_data' => 'La información proporcionada no es válida.', 'already_exists' => 'Ya existe una cuenta con este correo o indicativo.', 'too_many' => 'Demasiados intentos. Inténtelo más tarde.', 'ok_created' => 'Cuenta creada correctamente. ¡Bienvenido al área de miembros!', 'title' => 'Crear una cuenta de miembro', 'callsign' => 'Indicativo', 'full_name' => 'Nombre completo', 'email' => 'Correo electrónico', 'password' => 'Contraseña', 'submit' => 'Crear mi cuenta', 'already_registered' => '¿Ya registrado?', 'login' => 'Iniciar sesión', 'layout_title' => 'Registro'],
    'it' => ['required' => 'Tutti i campi sono obbligatori.', 'auth_unavailable' => 'Modulo di autenticazione non disponibile. Esegui composer install.', 'invalid_data' => 'Le informazioni fornite non sono valide.', 'already_exists' => 'Esiste già un account con questa email o nominativo.', 'too_many' => 'Troppi tentativi. Riprova più tardi.', 'ok_created' => 'Account creato con successo. Benvenuto nell’area membri!', 'title' => 'Crea un account membro', 'callsign' => 'Nominativo', 'full_name' => 'Nome completo', 'email' => 'Email', 'password' => 'Password', 'submit' => 'Crea il mio account', 'already_registered' => 'Già registrato?', 'login' => 'Accedi', 'layout_title' => 'Registrazione'],
    'pt' => ['required' => 'Todos os campos são obrigatórios.', 'auth_unavailable' => 'Módulo de autenticação indisponível. Execute composer install.', 'invalid_data' => 'Os dados fornecidos são inválidos.', 'already_exists' => 'Já existe uma conta com este email ou indicativo.', 'too_many' => 'Demasiadas tentativas. Tente novamente mais tarde.', 'ok_created' => 'Conta criada com sucesso. Bem-vindo à área de membros!', 'title' => 'Criar conta de membro', 'callsign' => 'Indicativo', 'full_name' => 'Nome completo', 'email' => 'Email', 'password' => 'Palavra-passe', 'submit' => 'Criar a minha conta', 'already_registered' => 'Já registado?', 'login' => 'Iniciar sessão', 'layout_title' => 'Registo'],
    'nl' => ['required' => 'Alle velden zijn verplicht.', 'auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'invalid_data' => 'De opgegeven gegevens zijn ongeldig.', 'already_exists' => 'Er bestaat al een account met dit e-mailadres of deze roepnaam.', 'too_many' => 'Te veel pogingen. Probeer het later opnieuw.', 'ok_created' => 'Account succesvol aangemaakt. Welkom in de ledenruimte!', 'title' => 'Ledenaccount aanmaken', 'callsign' => 'Roepnaam', 'full_name' => 'Volledige naam', 'email' => 'E-mail', 'password' => 'Wachtwoord', 'submit' => 'Mijn account aanmaken', 'already_registered' => 'Al geregistreerd?', 'login' => 'Inloggen', 'layout_title' => 'Registratie'],
    'ar' => ['required' => 'جميع الحقول مطلوبة.', 'auth_unavailable' => 'وحدة المصادقة غير متاحة. شغّل composer install.', 'invalid_data' => 'المعلومات المقدمة غير صالحة.', 'already_exists' => 'يوجد حساب بالفعل بهذا البريد الإلكتروني أو إشارة النداء.', 'too_many' => 'محاولات كثيرة جداً. يرجى المحاولة لاحقاً.', 'ok_created' => 'تم إنشاء الحساب بنجاح. أهلاً بك في مساحة الأعضاء!', 'title' => 'إنشاء حساب عضو', 'callsign' => 'إشارة النداء', 'full_name' => 'الاسم الكامل', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور', 'submit' => 'إنشاء حسابي', 'already_registered' => 'مسجل بالفعل؟', 'login' => 'تسجيل الدخول', 'layout_title' => 'التسجيل'],
    'bn' => ['required' => 'সব ক্ষেত্র আবশ্যক।', 'auth_unavailable' => 'প্রমাণীকরণ মডিউল উপলব্ধ নয়। composer install চালান।', 'invalid_data' => 'প্রদত্ত তথ্য বৈধ নয়।', 'already_exists' => 'এই ইমেইল বা কলসাইন দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট আছে।', 'too_many' => 'অনেক বেশি চেষ্টা হয়েছে। পরে আবার চেষ্টা করুন।', 'ok_created' => 'অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে। সদস্য এলাকায় স্বাগতম!', 'title' => 'সদস্য অ্যাকাউন্ট তৈরি করুন', 'callsign' => 'কলসাইন', 'full_name' => 'পূর্ণ নাম', 'email' => 'ইমেইল', 'password' => 'পাসওয়ার্ড', 'submit' => 'আমার অ্যাকাউন্ট তৈরি করুন', 'already_registered' => 'ইতিমধ্যে নিবন্ধিত?', 'login' => 'লগ ইন', 'layout_title' => 'নিবন্ধন'],
    'hi' => ['required' => 'सभी फ़ील्ड अनिवार्य हैं।', 'auth_unavailable' => 'प्रमाणीकरण मॉड्यूल उपलब्ध नहीं है। composer install चलाएँ।', 'invalid_data' => 'दी गई जानकारी अमान्य है।', 'already_exists' => 'इस ईमेल या कॉलसाइन से पहले से एक खाता मौजूद है।', 'too_many' => 'बहुत अधिक प्रयास। कृपया बाद में फिर कोशिश करें।', 'ok_created' => 'खाता सफलतापूर्वक बनाया गया। सदस्य क्षेत्र में आपका स्वागत है!', 'title' => 'सदस्य खाता बनाएँ', 'callsign' => 'कॉलसाइन', 'full_name' => 'पूरा नाम', 'email' => 'ईमेल', 'password' => 'पासवर्ड', 'submit' => 'मेरा खाता बनाएँ', 'already_registered' => 'पहले से पंजीकृत?', 'login' => 'लॉग इन करें', 'layout_title' => 'पंजीकरण'],
    'id' => ['required' => 'Semua bidang wajib diisi.', 'auth_unavailable' => 'Modul autentikasi tidak tersedia. Jalankan composer install.', 'invalid_data' => 'Informasi yang diberikan tidak valid.', 'already_exists' => 'Akun dengan email atau callsign ini sudah ada.', 'too_many' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.', 'ok_created' => 'Akun berhasil dibuat. Selamat datang di area anggota!', 'title' => 'Buat akun anggota', 'callsign' => 'Callsign', 'full_name' => 'Nama lengkap', 'email' => 'Email', 'password' => 'Kata sandi', 'submit' => 'Buat akun saya', 'already_registered' => 'Sudah terdaftar?', 'login' => 'Masuk', 'layout_title' => 'Pendaftaran'],
    'ja' => ['required' => 'すべての項目は必須です。', 'auth_unavailable' => '認証モジュールを利用できません。composer install を実行してください。', 'invalid_data' => '入力された情報が無効です。', 'already_exists' => 'このメールまたはコールサインのアカウントは既に存在します。', 'too_many' => '試行回数が多すぎます。後でもう一度お試しください。', 'ok_created' => 'アカウントを作成しました。会員エリアへようこそ！', 'title' => '会員アカウントを作成', 'callsign' => 'コールサイン', 'full_name' => '氏名', 'email' => 'メール', 'password' => 'パスワード', 'submit' => 'アカウントを作成', 'already_registered' => '登録済みですか？', 'login' => 'ログイン', 'layout_title' => '登録'],
    'ru' => ['required' => 'Все поля обязательны.', 'auth_unavailable' => 'Модуль аутентификации недоступен. Выполните composer install.', 'invalid_data' => 'Указанные данные недействительны.', 'already_exists' => 'Учётная запись с этим email или позывным уже существует.', 'too_many' => 'Слишком много попыток. Повторите позже.', 'ok_created' => 'Учётная запись успешно создана. Добро пожаловать в раздел участников!', 'title' => 'Создать учётную запись участника', 'callsign' => 'Позывной', 'full_name' => 'Полное имя', 'email' => 'Email', 'password' => 'Пароль', 'submit' => 'Создать мой аккаунт', 'already_registered' => 'Уже зарегистрированы?', 'login' => 'Войти', 'layout_title' => 'Регистрация'],
    'zh' => ['required' => '所有字段均为必填。', 'auth_unavailable' => '认证模块不可用。请运行 composer install。', 'invalid_data' => '提供的信息无效。', 'already_exists' => '已存在使用此电子邮箱或呼号的账户。', 'too_many' => '尝试次数过多。请稍后再试。', 'ok_created' => '账户创建成功。欢迎进入会员区！', 'title' => '创建会员账户', 'callsign' => '呼号', 'full_name' => '全名', 'email' => '电子邮箱', 'password' => '密码', 'submit' => '创建我的账户', 'already_registered' => '已经注册？', 'login' => '登录', 'layout_title' => '注册'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($callsign === '' || $fullName === '' || $email === '' || $password === '') {
            throw new RuntimeException($t('required'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('auth_unavailable'));
        }

        try {
            $userId = $authClient->registerWithUniqueUsername($email, $password, $callsign);
        } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException|\Delight\Auth\InvalidUsernameException $exception) {
            throw new RuntimeException($t('invalid_data'));
        } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
            throw new RuntimeException($t('already_exists'));
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException($t('too_many'));
        }

        db()->prepare(
            'INSERT INTO members (auth_user_id, callsign, full_name, email, password_hash, is_active)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                 callsign = VALUES(callsign),
                 full_name = VALUES(full_name),
                 email = VALUES(email),
                 password_hash = VALUES(password_hash),
                 is_active = 1'
        )->execute([(int) $userId, $callsign, $fullName, $email, password_hash($password, PASSWORD_DEFAULT)]);

        $authClient->loginWithUsername($callsign, $password);
        $_SESSION['member_id'] = (int) $authClient->getUserId();

        set_flash('success', $t('ok_created'));
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('register');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e($t('callsign')) . '<input type="text" name="callsign" maxlength="32" required></label>'
    . '<label>' . e($t('full_name')) . '<input type="text" name="full_name" maxlength="190" required></label>'
    . '<label>' . e($t('email')) . '<input type="email" name="email" maxlength="190" required></label>'
    . '<label>' . e($t('password')) . '<input type="password" name="password" minlength="8" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p>' . e($t('already_registered')) . ' <a href="' . e(route_url('login')) . '">' . e($t('login')) . '</a></p>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
