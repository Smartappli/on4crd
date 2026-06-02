<?php
declare(strict_types=1);

function ensure_storage_htaccess(string $directory, string $rules): void
{
    $file = rtrim($directory, '/') . '/.htaccess';
    if (!is_file($file)) {
        file_put_contents($file, $rules);
    }
}

function detect_uploaded_mime_type(string $tmpPath): string
{
    if (!is_file($tmpPath)) {
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return '';
    }
    $mime = (string) finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    return strtolower(trim($mime));
}

function upload_i18n_message(string $key): string
{
    $locale = current_locale();
    $messages = [
        'uploaded_unreadable' => ['fr' => 'Fichier téléversé illisible.', 'en' => 'Uploaded file is unreadable.', 'de' => 'Hochgeladene Datei ist unlesbar.', 'nl' => 'Geüpload bestand is onleesbaar.', 'es' => 'El archivo subido no se puede leer.', 'it' => 'Il file caricato non è leggibile.', 'pt' => 'O ficheiro carregado está ilegível.', 'ar' => 'الملف المرفوع غير قابل للقراءة.', 'hi' => 'अपलोड की गई फ़ाइल पढ़ी नहीं जा सकती।', 'ja' => 'アップロードされたファイルを読み取れません。', 'zh' => '上传的文件无法读取。', 'bn' => 'আপলোড করা ফাইলটি পড়া যাচ্ছে না।', 'ru' => 'Загруженный файл не читается.', 'id' => 'File yang diunggah tidak dapat dibaca.'],
        'invalid_signature' => ['fr' => 'Signature de fichier invalide pour le type attendu.', 'en' => 'Invalid file signature for the expected type.', 'de' => 'Ungültige Dateisignatur für den erwarteten Typ.', 'nl' => 'Ongeldige bestandssignatuur voor het verwachte type.', 'es' => 'Firma de archivo no válida para el tipo esperado.', 'it' => 'Firma file non valida per il tipo previsto.', 'pt' => 'Assinatura de ficheiro inválida para o tipo esperado.', 'ar' => 'توقيع الملف غير صالح للنوع المتوقع.', 'hi' => 'अपेक्षित प्रकार के लिए फ़ाइल हस्ताक्षर अमान्य है।', 'ja' => '想定された形式に対してファイル署名が無効です。', 'zh' => '文件签名与预期类型不匹配。', 'bn' => 'প্রত্যাশিত ধরনের জন্য ফাইল স্বাক্ষর অবৈধ।', 'ru' => 'Недопустимая сигнатура файла для ожидаемого типа.', 'id' => 'Tanda tangan file tidak valid untuk tipe yang diharapkan.'],
        'upload_failed' => ['fr' => 'Échec du téléversement.', 'en' => 'Upload failed.', 'de' => 'Upload fehlgeschlagen.', 'nl' => 'Upload mislukt.', 'es' => 'Error al subir el archivo.', 'it' => 'Caricamento non riuscito.', 'pt' => 'Falha no carregamento.', 'ar' => 'فشل رفع الملف.', 'hi' => 'अपलोड विफल हुआ।', 'ja' => 'アップロードに失敗しました。', 'zh' => '上传失败。', 'bn' => 'আপলোড ব্যর্থ হয়েছে।', 'ru' => 'Ошибка загрузки файла.', 'id' => 'Unggahan gagal.'],
        'upload_invalid' => ['fr' => 'Fichier téléversé invalide.', 'en' => 'Invalid uploaded file.', 'de' => 'Ungültig hochgeladene Datei.', 'nl' => 'Ongeldig geüpload bestand.', 'es' => 'Archivo subido no válido.', 'it' => 'File caricato non valido.', 'pt' => 'Ficheiro carregado inválido.', 'ar' => 'الملف المرفوع غير صالح.', 'hi' => 'अपलोड की गई फ़ाइल अमान्य है।', 'ja' => '無効なアップロードファイルです。', 'zh' => '上传的文件无效。', 'bn' => 'আপলোড করা ফাইলটি অবৈধ।', 'ru' => 'Недопустимый загруженный файл.', 'id' => 'File yang diunggah tidak valid.'],
        'file_too_large_or_empty' => ['fr' => 'Fichier trop volumineux ou vide.', 'en' => 'File is too large or empty.', 'de' => 'Datei ist zu groß oder leer.', 'nl' => 'Bestand is te groot of leeg.', 'es' => 'El archivo es demasiado grande o está vacío.', 'it' => 'Il file è troppo grande o vuoto.', 'pt' => 'O ficheiro é demasiado grande ou está vazio.', 'ar' => 'الملف كبير جدًا أو فارغ.', 'hi' => 'फ़ाइल बहुत बड़ी है या खाली है।', 'ja' => 'ファイルが大きすぎるか空です。', 'zh' => '文件过大或为空。', 'bn' => 'ফাইলটি খুব বড় বা খালি।', 'ru' => 'Файл слишком большой или пустой.', 'id' => 'File terlalu besar atau kosong.'],
        'extension_not_allowed' => ['fr' => 'Extension de fichier non autorisée.', 'en' => 'File extension is not allowed.', 'de' => 'Dateierweiterung ist nicht erlaubt.', 'nl' => 'Bestandsextensie is niet toegestaan.', 'es' => 'La extensión de archivo no está permitida.', 'it' => 'Estensione file non consentita.', 'pt' => 'Extensão de ficheiro não permitida.', 'ar' => 'امتداد الملف غير مسموح به.', 'hi' => 'फ़ाइल एक्सटेंशन की अनुमति नहीं है।', 'ja' => '許可されていないファイル拡張子です。', 'zh' => '文件扩展名不被允许。', 'bn' => 'ফাইল এক্সটেনশন অনুমোদিত নয়।', 'ru' => 'Расширение файла не разрешено.', 'id' => 'Ekstensi file tidak diizinkan.'],
        'mime_not_allowed' => ['fr' => 'Type MIME de fichier non autorisé.', 'en' => 'File MIME type is not allowed.', 'de' => 'MIME-Typ der Datei ist nicht erlaubt.', 'nl' => 'MIME-type van bestand is niet toegestaan.', 'es' => 'El tipo MIME del archivo no está permitido.', 'it' => 'Il tipo MIME del file non è consentito.', 'pt' => 'O tipo MIME do ficheiro não é permitido.', 'ar' => 'نوع MIME للملف غير مسموح به.', 'hi' => 'फ़ाइल का MIME प्रकार अनुमत नहीं है।', 'ja' => '許可されていない MIME タイプです。', 'zh' => '文件 MIME 类型不被允许。', 'bn' => 'ফাইলের MIME ধরন অনুমোদিত নয়।', 'ru' => 'MIME-тип файла не разрешён.', 'id' => 'Tipe MIME file tidak diizinkan.'],
        'cannot_create_destination_dir' => ['fr' => 'Impossible de créer le dossier de destination.', 'en' => 'Unable to create destination folder.', 'de' => 'Zielordner konnte nicht erstellt werden.', 'nl' => 'Kan doelmap niet maken.', 'es' => 'No se puede crear la carpeta de destino.', 'it' => 'Impossibile creare la cartella di destinazione.', 'pt' => 'Não foi possível criar a pasta de destino.', 'ar' => 'تعذر إنشاء مجلد الوجهة.', 'hi' => 'गंतव्य फ़ोल्डर बनाया नहीं जा सका।', 'ja' => '保存先フォルダーを作成できません。', 'zh' => '无法创建目标文件夹。', 'bn' => 'গন্তব্য ফোল্ডার তৈরি করা যায়নি।', 'ru' => 'Не удалось создать целевую папку.', 'id' => 'Tidak dapat membuat folder tujuan.'],
        'cannot_move_uploaded_file' => ['fr' => 'Impossible de déplacer le fichier téléversé.', 'en' => 'Unable to move uploaded file.', 'de' => 'Hochgeladene Datei konnte nicht verschoben werden.', 'nl' => 'Kan geüpload bestand niet verplaatsen.', 'es' => 'No se puede mover el archivo subido.', 'it' => 'Impossibile spostare il file caricato.', 'pt' => 'Não foi possível mover o ficheiro carregado.', 'ar' => 'تعذر نقل الملف المرفوع.', 'hi' => 'अपलोड की गई फ़ाइल को स्थानांतरित नहीं किया जा सका।', 'ja' => 'アップロードファイルを移動できません。', 'zh' => '无法移动上传的文件。', 'bn' => 'আপলোড করা ফাইল সরানো যায়নি।', 'ru' => 'Не удалось переместить загруженный файл.', 'id' => 'Tidak dapat memindahkan file yang diunggah.'],
        'uploaded_image_unreadable' => ['fr' => 'Image téléversée illisible.', 'en' => 'Uploaded image is unreadable.', 'de' => 'Hochgeladenes Bild ist unlesbar.', 'nl' => 'Geüploade afbeelding is onleesbaar.', 'es' => 'La imagen subida no se puede leer.', 'it' => 'L’immagine caricata non è leggibile.', 'pt' => 'A imagem carregada está ilegível.', 'ar' => 'الصورة المرفوعة غير قابلة للقراءة.', 'hi' => 'अपलोड की गई छवि पढ़ी नहीं जा सकती।', 'ja' => 'アップロードされた画像を読み取れません。', 'zh' => '上传的图片无法读取。', 'bn' => 'আপলোড করা ছবিটি পড়া যাচ্ছে না।', 'ru' => 'Загруженное изображение не читается.', 'id' => 'Gambar yang diunggah tidak dapat dibaca.'],
        'uploaded_image_invalid' => ['fr' => 'Image téléversée invalide.', 'en' => 'Uploaded image is invalid.', 'de' => 'Hochgeladenes Bild ist ungültig.', 'nl' => 'Geüploade afbeelding is ongeldig.', 'es' => 'La imagen subida no es válida.', 'it' => 'L’immagine caricata non è valida.', 'pt' => 'A imagem carregada é inválida.', 'ar' => 'الصورة المرفوعة غير صالحة.', 'hi' => 'अपलोड की गई छवि अमान्य है।', 'ja' => 'アップロードされた画像が無効です。', 'zh' => '上传的图片无效。', 'bn' => 'আপলোড করা ছবিটি অবৈধ।', 'ru' => 'Загруженное изображение недопустимо.', 'id' => 'Gambar yang diunggah tidak valid.'],
        'cannot_create_temp_file' => ['fr' => 'Impossible de créer un fichier temporaire.', 'en' => 'Unable to create a temporary file.', 'de' => 'Temporäre Datei konnte nicht erstellt werden.', 'nl' => 'Kan geen tijdelijk bestand maken.', 'es' => 'No se puede crear un archivo temporal.', 'it' => 'Impossibile creare un file temporaneo.', 'pt' => 'Não foi possível criar um ficheiro temporário.', 'ar' => 'تعذر إنشاء ملف مؤقت.', 'hi' => 'अस्थायी फ़ाइल बनाई नहीं जा सकी।', 'ja' => '一時ファイルを作成できません。', 'zh' => '无法创建临时文件。', 'bn' => 'অস্থায়ী ফাইল তৈরি করা যায়নি।', 'ru' => 'Не удалось создать временный файл.', 'id' => 'Tidak dapat membuat file sementara.'],
        'image_metadata_cleanup_failed' => ['fr' => 'Échec du nettoyage des métadonnées image.', 'en' => 'Failed to clean image metadata.', 'de' => 'Bereinigung der Bildmetadaten fehlgeschlagen.', 'nl' => 'Opschonen van afbeeldingsmetadata mislukt.', 'es' => 'Error al limpiar los metadatos de la imagen.', 'it' => 'Pulizia dei metadati immagine non riuscita.', 'pt' => 'Falha ao limpar os metadados da imagem.', 'ar' => 'فشل تنظيف البيانات الوصفية للصورة.', 'hi' => 'छवि मेटाडेटा साफ़ करने में विफल।', 'ja' => '画像メタデータのクリーンアップに失敗しました。', 'zh' => '清理图片元数据失败。', 'bn' => 'ছবির মেটাডেটা পরিষ্কার করা যায়নি।', 'ru' => 'Не удалось очистить метаданные изображения.', 'id' => 'Gagal membersihkan metadata gambar.'],
        'missing_image' => ['fr' => 'Image manquante.', 'en' => 'Missing image.', 'de' => 'Bild fehlt.', 'nl' => 'Afbeelding ontbreekt.', 'es' => 'Falta la imagen.', 'it' => 'Immagine mancante.', 'pt' => 'Imagem em falta.', 'ar' => 'الصورة مفقودة.', 'hi' => 'छवि अनुपलब्ध है।', 'ja' => '画像がありません。', 'zh' => '缺少图片。', 'bn' => 'ছবি অনুপস্থিত।', 'ru' => 'Изображение отсутствует.', 'id' => 'Gambar tidak ada.'],
        'qsl_bg_upload_failed' => ['fr' => 'Le téléversement de l’image de fond QSL a échoué.', 'en' => 'QSL background image upload failed.', 'de' => 'Das Hochladen des QSL-Hintergrundbilds ist fehlgeschlagen.', 'nl' => 'Upload van QSL-achtergrondafbeelding mislukt.', 'es' => 'Error al subir la imagen de fondo QSL.', 'it' => 'Caricamento dell’immagine di sfondo QSL non riuscito.', 'pt' => 'Falha no carregamento da imagem de fundo QSL.', 'ar' => 'فشل رفع صورة خلفية QSL.', 'hi' => 'QSL पृष्ठभूमि छवि अपलोड विफल हुआ।', 'ja' => 'QSL 背景画像のアップロードに失敗しました。', 'zh' => 'QSL 背景图片上传失败。', 'bn' => 'QSL ব্যাকগ্রাউন্ড ছবি আপলোড ব্যর্থ হয়েছে।', 'ru' => 'Не удалось загрузить фоновое изображение QSL.', 'id' => 'Unggahan gambar latar QSL gagal.'],
        'qsl_bg_invalid' => ['fr' => 'Image de fond QSL invalide.', 'en' => 'Invalid QSL background image.', 'de' => 'Ungültiges QSL-Hintergrundbild.', 'nl' => 'Ongeldige QSL-achtergrondafbeelding.', 'es' => 'Imagen de fondo QSL no válida.', 'it' => 'Immagine di sfondo QSL non valida.', 'pt' => 'Imagem de fundo QSL inválida.', 'ar' => 'صورة خلفية QSL غير صالحة.', 'hi' => 'अमान्य QSL पृष्ठभूमि छवि।', 'ja' => '無効な QSL 背景画像です。', 'zh' => '无效的 QSL 背景图片。', 'bn' => 'অবৈধ QSL ব্যাকগ্রাউন্ড ছবি।', 'ru' => 'Недопустимое фоновое изображение QSL.', 'id' => 'Gambar latar QSL tidak valid.'],
        'qsl_bg_not_supported' => ['fr' => 'Image de fond non supportée (JPG, PNG ou WEBP).', 'en' => 'Unsupported background image (JPG, PNG or WEBP).', 'de' => 'Nicht unterstütztes Hintergrundbild (JPG, PNG oder WEBP).', 'nl' => 'Niet-ondersteunde achtergrondafbeelding (JPG, PNG of WEBP).', 'es' => 'Imagen de fondo no compatible (JPG, PNG o WEBP).', 'it' => 'Immagine di sfondo non supportata (JPG, PNG o WEBP).', 'pt' => 'Imagem de fundo não suportada (JPG, PNG ou WEBP).', 'ar' => 'صورة الخلفية غير مدعومة (JPG أو PNG أو WEBP).', 'hi' => 'असमर्थित पृष्ठभूमि छवि (JPG, PNG या WEBP)।', 'ja' => '未対応の背景画像です（JPG、PNG、WEBP）。', 'zh' => '不支持的背景图片（JPG、PNG 或 WEBP）。', 'bn' => 'অসমর্থিত ব্যাকগ্রাউন্ড ছবি (JPG, PNG বা WEBP)।', 'ru' => 'Неподдерживаемое фоновое изображение (JPG, PNG или WEBP).', 'id' => 'Gambar latar tidak didukung (JPG, PNG, atau WEBP).'],
        'qsl_bg_too_large' => ['fr' => 'Image de fond trop volumineuse (max 6 Mo).', 'en' => 'Background image is too large (max 6 MB).', 'de' => 'Hintergrundbild ist zu groß (max. 6 MB).', 'nl' => 'Achtergrondafbeelding is te groot (max. 6 MB).', 'es' => 'La imagen de fondo es demasiado grande (máx. 6 MB).', 'it' => 'L’immagine di sfondo è troppo grande (max 6 MB).', 'pt' => 'A imagem de fundo é demasiado grande (máx. 6 MB).', 'ar' => 'صورة الخلفية كبيرة جدًا (الحد الأقصى 6 ميغابايت).', 'hi' => 'पृष्ठभूमि छवि बहुत बड़ी है (अधिकतम 6 MB)।', 'ja' => '背景画像が大きすぎます（最大6MB）。', 'zh' => '背景图片过大（最大 6MB）。', 'bn' => 'ব্যাকগ্রাউন্ড ছবি খুব বড় (সর্বোচ্চ 6 MB)।', 'ru' => 'Фоновое изображение слишком большое (макс. 6 МБ).', 'id' => 'Gambar latar terlalu besar (maks 6 MB).'],
        'qsl_bg_unreadable' => ['fr' => 'Image de fond QSL illisible.', 'en' => 'QSL background image is unreadable.', 'de' => 'QSL-Hintergrundbild ist unlesbar.', 'nl' => 'QSL-achtergrondafbeelding is onleesbaar.', 'es' => 'La imagen de fondo QSL no se puede leer.', 'it' => 'L’immagine di sfondo QSL non è leggibile.', 'pt' => 'A imagem de fundo QSL está ilegível.', 'ar' => 'صورة خلفية QSL غير قابلة للقراءة.', 'hi' => 'QSL पृष्ठभूमि छवि पढ़ी नहीं जा सकती।', 'ja' => 'QSL 背景画像を読み取れません。', 'zh' => 'QSL 背景图片无法读取。', 'bn' => 'QSL ব্যাকগ্রাউন্ড ছবি পড়া যাচ্ছে না।', 'ru' => 'Фоновое изображение QSL не читается.', 'id' => 'Gambar latar QSL tidak dapat dibaca.'],
        'invalid_csrf_token' => ['fr' => 'Jeton CSRF invalide.', 'en' => 'Invalid CSRF token.', 'de' => 'Ungültiges CSRF-Token.', 'nl' => 'Ongeldig CSRF-token.', 'es' => 'Token CSRF no válido.', 'it' => 'Token CSRF non valido.', 'pt' => 'Token CSRF inválido.', 'ar' => 'رمز CSRF غير صالح.', 'hi' => 'अमान्य CSRF टोकन।', 'ja' => '無効な CSRF トークンです。', 'zh' => '无效的 CSRF 令牌。', 'bn' => 'অবৈধ CSRF টোকেন।', 'ru' => 'Недействительный CSRF-токен.', 'id' => 'Token CSRF tidak valid.'],
        'too_many_login_attempts' => ['fr' => 'Trop de tentatives de connexion. Réessayez plus tard.', 'en' => 'Too many login attempts. Please try again later.', 'de' => 'Zu viele Anmeldeversuche. Bitte später erneut versuchen.', 'nl' => 'Te veel inlogpogingen. Probeer het later opnieuw.', 'es' => 'Demasiados intentos de inicio de sesión. Inténtelo más tarde.', 'it' => 'Troppi tentativi di accesso. Riprova più tardi.', 'pt' => 'Muitas tentativas de início de sessão. Tente novamente mais tarde.', 'ar' => 'محاولات تسجيل دخول كثيرة جدًا. يرجى المحاولة لاحقًا.', 'hi' => 'लॉगिन के बहुत अधिक प्रयास हुए। कृपया बाद में पुनः प्रयास करें।', 'ja' => 'ログイン試行回数が多すぎます。しばらくしてから再試行してください。', 'zh' => '登录尝试次数过多，请稍后再试。', 'bn' => 'লগইনের চেষ্টা খুব বেশি হয়েছে। অনুগ্রহ করে পরে আবার চেষ্টা করুন।', 'ru' => 'Слишком много попыток входа. Повторите позже.', 'id' => 'Terlalu banyak percobaan masuk. Silakan coba lagi nanti.'],
        'invalid_url' => ['fr' => 'URL invalide.', 'en' => 'Invalid URL.', 'de' => 'Ungültige URL.', 'nl' => 'Ongeldige URL.', 'es' => 'URL no válida.', 'it' => 'URL non valido.', 'pt' => 'URL inválido.', 'ar' => 'رابط غير صالح.', 'hi' => 'अमान्य URL।', 'ja' => '無効なURLです。', 'zh' => '无效的 URL。', 'bn' => 'অবৈধ URL।', 'ru' => 'Недопустимый URL.', 'id' => 'URL tidak valid.'],
        'invalid_relative_url' => ['fr' => 'URL relative invalide.', 'en' => 'Invalid relative URL.', 'de' => 'Ungültige relative URL.', 'nl' => 'Ongeldige relatieve URL.', 'es' => 'URL relativa no válida.', 'it' => 'URL relativo non valido.', 'pt' => 'URL relativo inválido.', 'ar' => 'رابط نسبي غير صالح.', 'hi' => 'अमान्य सापेक्ष URL।', 'ja' => '無効な相対URLです。', 'zh' => '无效的相对 URL。', 'bn' => 'অবৈধ রিলেটিভ URL।', 'ru' => 'Недопустимый относительный URL.', 'id' => 'URL relatif tidak valid.'],
        'only_http_https_allowed' => ['fr' => 'Seules les URL HTTP et HTTPS sont autorisées.', 'en' => 'Only HTTP and HTTPS URLs are allowed.', 'de' => 'Nur HTTP- und HTTPS-URLs sind erlaubt.', 'nl' => 'Alleen HTTP- en HTTPS-URL’s zijn toegestaan.', 'es' => 'Solo se permiten URL HTTP y HTTPS.', 'it' => 'Sono consentiti solo URL HTTP e HTTPS.', 'pt' => 'Apenas URLs HTTP e HTTPS são permitidos.', 'ar' => 'يُسمح فقط بروابط HTTP وHTTPS.', 'hi' => 'केवल HTTP और HTTPS URL की अनुमति है।', 'ja' => 'HTTP および HTTPS のURLのみ許可されています。', 'zh' => '仅允许 HTTP 和 HTTPS URL。', 'bn' => 'শুধুমাত্র HTTP এবং HTTPS URL অনুমোদিত।', 'ru' => 'Разрешены только URL HTTP и HTTPS.', 'id' => 'Hanya URL HTTP dan HTTPS yang diizinkan.'],
        'invalid_product' => ['fr' => 'Produit invalide.', 'en' => 'Invalid product.', 'de' => 'Ungültiges Produkt.', 'nl' => 'Ongeldig product.', 'es' => 'Producto no válido.', 'it' => 'Prodotto non valido.', 'pt' => 'Produto inválido.', 'ar' => 'منتج غير صالح.', 'hi' => 'अमान्य उत्पाद।', 'ja' => '無効な商品です。', 'zh' => '无效商品。', 'bn' => 'অবৈধ পণ্য।', 'ru' => 'Недопустимый товар.', 'id' => 'Produk tidak valid.'],
        'product_unavailable' => ['fr' => 'Produit indisponible.', 'en' => 'Product unavailable.', 'de' => 'Produkt nicht verfügbar.', 'nl' => 'Product niet beschikbaar.', 'es' => 'Producto no disponible.', 'it' => 'Prodotto non disponibile.', 'pt' => 'Produto indisponível.', 'ar' => 'المنتج غير متاح.', 'hi' => 'उत्पाद उपलब्ध नहीं है।', 'ja' => '商品は利用できません。', 'zh' => '商品不可用。', 'bn' => 'পণ্য উপলভ্য নয়।', 'ru' => 'Товар недоступен.', 'id' => 'Produk tidak tersedia.'],
        'invalid_product_in_cart' => ['fr' => 'Produit invalide dans le panier.', 'en' => 'Invalid product in cart.', 'de' => 'Ungültiges Produkt im Warenkorb.', 'nl' => 'Ongeldig product in de winkelwagen.', 'es' => 'Producto no válido en el carrito.', 'it' => 'Prodotto non valido nel carrello.', 'pt' => 'Produto inválido no carrinho.', 'ar' => 'منتج غير صالح في السلة.', 'hi' => 'कार्ट में अमान्य उत्पाद।', 'ja' => 'カート内に無効な商品があります。', 'zh' => '购物车中有无效商品。', 'bn' => 'কার্টে অবৈধ পণ্য।', 'ru' => 'Недопустимый товар в корзине.', 'id' => 'Produk tidak valid di keranjang.'],
        'cart_empty' => ['fr' => 'Le panier est vide.', 'en' => 'Cart is empty.', 'de' => 'Der Warenkorb ist leer.', 'nl' => 'Winkelwagen is leeg.', 'es' => 'El carrito está vacío.', 'it' => 'Il carrello è vuoto.', 'pt' => 'O carrinho está vazio.', 'ar' => 'سلة التسوق فارغة.', 'hi' => 'कार्ट खाली है।', 'ja' => 'カートは空です。', 'zh' => '购物车为空。', 'bn' => 'কার্ট খালি।', 'ru' => 'Корзина пуста.', 'id' => 'Keranjang kosong.'],
        'insufficient_stock_for' => ['fr' => 'Stock insuffisant pour ', 'en' => 'Insufficient stock for ', 'de' => 'Unzureichender Lagerbestand für ', 'nl' => 'Onvoldoende voorraad voor ', 'es' => 'Stock insuficiente para ', 'it' => 'Scorte insufficienti per ', 'pt' => 'Stock insuficiente para ', 'ar' => 'المخزون غير كافٍ لـ ', 'hi' => 'के लिए स्टॉक अपर्याप्त है: ', 'ja' => '在庫不足: ', 'zh' => '库存不足：', 'bn' => 'এর জন্য পর্যাপ্ত স্টক নেই: ', 'ru' => 'Недостаточно товара для ', 'id' => 'Stok tidak mencukupi untuk '],
    ];
    return $messages[$key][$locale] ?? $messages[$key]['fr'] ?? '';
}

function assert_upload_file_is_valid_signature(string $tmpPath, array $allowedExtensions): void
{
    $signature = @file_get_contents($tmpPath, false, null, 0, 16);
    if ($signature === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_unreadable'));
    }

    $known = [
        'pdf' => '%PDF-',
        'jpg' => "\xFF\xD8\xFF",
        'jpeg' => "\xFF\xD8\xFF",
        'png' => "\x89PNG\r\n\x1A\n",
        'webp' => 'RIFF',
    ];

    foreach ($allowedExtensions as $extension) {
        $extension = strtolower((string) $extension);
        if (!isset($known[$extension])) {
            continue;
        }
        if (str_starts_with($signature, $known[$extension])) {
            if ($extension !== 'webp' || str_contains(substr($signature, 8), 'WEBP')) {
                return;
            }
        }
    }

    throw new RuntimeException(upload_i18n_message('invalid_signature'));
}

function secure_move_uploaded_file(
    array $upload,
    string $destinationDirectory,
    string $prefix,
    array $allowedExtensions,
    array $allowedMimes,
    int $maxBytes
): string {
    $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_i18n_message('upload_failed'));
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException(upload_i18n_message('upload_invalid'));
    }

    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException(upload_i18n_message('file_too_large_or_empty'));
    }

    $originalName = (string) ($upload['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException(upload_i18n_message('extension_not_allowed'));
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException(upload_i18n_message('mime_not_allowed'));
    }
    assert_upload_file_is_valid_signature($tmpPath, $allowedExtensions);

    $sanitizedTmpPath = $tmpPath;
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    }

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        throw new RuntimeException(upload_i18n_message('cannot_create_destination_dir'));
    }

    $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destinationPath = rtrim($destinationDirectory, '/') . '/' . $filename;
    $moved = $sanitizedTmpPath === $tmpPath
        ? move_uploaded_file($tmpPath, $destinationPath)
        : rename($sanitizedTmpPath, $destinationPath);
    if (!$moved) {
        throw new RuntimeException(upload_i18n_message('cannot_move_uploaded_file'));
    }

    @chmod($destinationPath, 0644);
    return $filename;
}

function sanitize_uploaded_image_file(string $tmpPath, string $extension): string
{
    $raw = @file_get_contents($tmpPath);
    if ($raw === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_image_unreadable'));
    }

    if (!function_exists('imagecreatefromstring')) {
        return $tmpPath;
    }

    $image = @imagecreatefromstring($raw);
    if ($image === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_image_invalid'));
    }

    $outputPath = tempnam(sys_get_temp_dir(), 'on4crd-img-');
    if ($outputPath === false) {
        imagedestroy($image);
        throw new RuntimeException(upload_i18n_message('cannot_create_temp_file'));
    }

    $writeOk = match ($extension) {
        'jpg', 'jpeg' => imagejpeg($image, $outputPath, 90),
        'png' => imagepng($image, $outputPath, 6),
        'webp' => function_exists('imagewebp') ? imagewebp($image, $outputPath, 85) : false,
        default => false,
    };
    imagedestroy($image);

    if (!$writeOk) {
        @unlink($outputPath);
        throw new RuntimeException(upload_i18n_message('image_metadata_cleanup_failed'));
    }

    return $outputPath;
}
