<?php
declare(strict_types=1);

$root = dirname(__DIR__) . '/app/i18n';

$adminArticles = [
    'ar' => [
        'editorial_queue' => 'لوحة التحرير',
        'editorial_queue_help' => 'قائمة المواد المجدولة مع أسباب الحظر وإعادة المحاولة بنقرة واحدة.',
        'editorial_queue_empty' => 'لا توجد مقالات مجدولة في القائمة.',
        'retry' => 'إعادة المحاولة',
        'retry_bulk' => 'إعادة محاولة المحدد',
        'retry_blocked_missing_fields' => 'تعذرت إعادة المحاولة: أكمل العنوان والمحتوى أولاً.',
        'retry_applied' => 'تم تنفيذ إعادة المحاولة.',
        'retry_applied_published' => 'تم تنفيذ إعادة المحاولة: تم نشر المقال.',
        'retry_applied_rescheduled' => 'تم تنفيذ إعادة المحاولة: تمت إعادة الجدولة (+1h).',
        'retry_bulk_summary' => 'إعادة محاولة جماعية: %d منشور، %d أُعيدت جدولته، %d أُعيدت محاولته، %d محظور.',
        'blocked_reasons' => 'أسباب الحظر',
    ],
    'bn' => [
        'editorial_queue' => 'সম্পাদকীয় কিউ',
        'editorial_queue_help' => 'নির্ধারিত কনটেন্ট বোর্ড, ব্লকের কারণ ও এক-ক্লিক রিট্রাই।',
        'editorial_queue_empty' => 'কিউতে কোনো নির্ধারিত আর্টিকেল নেই।',
        'retry' => 'পুনরায় চেষ্টা',
        'retry_bulk' => 'নির্বাচিতগুলোর রিট্রাই',
        'retry_blocked_missing_fields' => 'রিট্রাই বন্ধ: আগে শিরোনাম ও বিষয়বস্তু পূরণ করুন।',
        'retry_applied' => 'রিট্রাই প্রয়োগ করা হয়েছে।',
        'retry_applied_published' => 'রিট্রাই প্রয়োগ: আর্টিকেল প্রকাশিত হয়েছে।',
        'retry_applied_rescheduled' => 'রিট্রাই প্রয়োগ: সময়সূচি নতুন করে সেট হয়েছে (+1h).',
        'retry_bulk_summary' => 'বাল্ক রিট্রাই: %d প্রকাশিত, %d পুনঃতফসিল, %d রিট্রাই, %d ব্লক।',
        'blocked_reasons' => 'ব্লকের কারণ',
    ],
    'de' => [
        'editorial_queue' => 'Redaktionswarteschlange',
        'editorial_queue_help' => 'Geplante Inhalte mit Sperrgründen und Ein-Klick-Wiederholung.',
        'editorial_queue_empty' => 'Keine geplanten Artikel in der Warteschlange.',
        'retry' => 'Erneut versuchen',
        'retry_bulk' => 'Auswahl erneut versuchen',
        'retry_blocked_missing_fields' => 'Wiederholung blockiert: Titel und Inhalt zuerst vervollständigen.',
        'retry_applied' => 'Wiederholung ausgeführt.',
        'retry_applied_published' => 'Wiederholung ausgeführt: Artikel veröffentlicht.',
        'retry_applied_rescheduled' => 'Wiederholung ausgeführt: Termin neu gesetzt (+1h).',
        'retry_bulk_summary' => 'Sammel-Wiederholung: %d veröffentlicht, %d neu geplant, %d erneut versucht, %d blockiert.',
        'blocked_reasons' => 'Sperrgründe',
    ],
    'en' => [
        'editorial_queue' => 'Editorial queue',
        'editorial_queue_help' => 'Scheduled board with blocked reasons and one-click retry.',
        'editorial_queue_empty' => 'No scheduled articles in queue.',
        'retry' => 'Retry',
        'retry_bulk' => 'Retry selected',
        'retry_blocked_missing_fields' => 'Retry blocked: complete title and content first.',
        'retry_applied' => 'Retry applied.',
        'retry_applied_published' => 'Retry applied: article published.',
        'retry_applied_rescheduled' => 'Retry applied: schedule reset (+1h).',
        'retry_bulk_summary' => 'Bulk retry: %d published, %d rescheduled, %d retried, %d blocked.',
        'blocked_reasons' => 'Blocked reasons',
    ],
    'es' => [
        'editorial_queue' => 'Cola editorial',
        'editorial_queue_help' => 'Panel de contenidos programados con motivos de bloqueo y reintento en un clic.',
        'editorial_queue_empty' => 'No hay artículos programados en la cola.',
        'retry' => 'Reintentar',
        'retry_bulk' => 'Reintentar selección',
        'retry_blocked_missing_fields' => 'Reintento bloqueado: complete primero título y contenido.',
        'retry_applied' => 'Reintento aplicado.',
        'retry_applied_published' => 'Reintento aplicado: artículo publicado.',
        'retry_applied_rescheduled' => 'Reintento aplicado: fecha reprogramada (+1h).',
        'retry_bulk_summary' => 'Reintento masivo: %d publicados, %d reprogramados, %d reintentados, %d bloqueados.',
        'blocked_reasons' => 'Motivos de bloqueo',
    ],
    'fr' => [
        'editorial_queue' => 'File editoriale',
        'editorial_queue_help' => 'Tableau des contenus planifies avec motifs de blocage et relance en un clic.',
        'editorial_queue_empty' => 'Aucun article planifie dans la file.',
        'retry' => 'Relancer',
        'retry_bulk' => 'Relancer la selection',
        'retry_blocked_missing_fields' => 'Relance bloquee : completez d abord le titre et le contenu.',
        'retry_applied' => 'Relance effectuee.',
        'retry_applied_published' => 'Relance effectuee : article publie.',
        'retry_applied_rescheduled' => 'Relance effectuee : planification reinitialisee (+1h).',
        'retry_bulk_summary' => 'Relance groupee : %d publies, %d replanifies, %d relances, %d bloques.',
        'blocked_reasons' => 'Motifs de blocage',
    ],
    'hi' => [
        'editorial_queue' => 'संपादकीय कतार',
        'editorial_queue_help' => 'निर्धारित सामग्री बोर्ड, अवरोध कारण और एक-क्लिक पुनःप्रयास।',
        'editorial_queue_empty' => 'कतार में कोई निर्धारित लेख नहीं है।',
        'retry' => 'फिर प्रयास करें',
        'retry_bulk' => 'चयनित पर फिर प्रयास',
        'retry_blocked_missing_fields' => 'पुनःप्रयास अवरुद्ध: पहले शीर्षक और सामग्री पूरी करें।',
        'retry_applied' => 'पुनःप्रयास लागू किया गया।',
        'retry_applied_published' => 'पुनःप्रयास लागू: लेख प्रकाशित किया गया।',
        'retry_applied_rescheduled' => 'पुनःप्रयास लागू: समय फिर निर्धारित (+1h).',
        'retry_bulk_summary' => 'समूह पुनःप्रयास: %d प्रकाशित, %d पुनर्निर्धारित, %d पुनःप्रयास, %d अवरुद्ध।',
        'blocked_reasons' => 'अवरोध कारण',
    ],
    'id' => [
        'editorial_queue' => 'Antrian editorial',
        'editorial_queue_help' => 'Papan konten terjadwal dengan alasan blokir dan coba lagi sekali klik.',
        'editorial_queue_empty' => 'Tidak ada artikel terjadwal dalam antrian.',
        'retry' => 'Coba lagi',
        'retry_bulk' => 'Coba lagi pilihan',
        'retry_blocked_missing_fields' => 'Coba lagi diblokir: lengkapi dulu judul dan konten.',
        'retry_applied' => 'Coba lagi diterapkan.',
        'retry_applied_published' => 'Coba lagi diterapkan: artikel dipublikasikan.',
        'retry_applied_rescheduled' => 'Coba lagi diterapkan: jadwal diatur ulang (+1h).',
        'retry_bulk_summary' => 'Coba lagi massal: %d dipublikasikan, %d dijadwal ulang, %d dicoba lagi, %d diblokir.',
        'blocked_reasons' => 'Alasan blokir',
    ],
    'it' => [
        'editorial_queue' => 'Coda editoriale',
        'editorial_queue_help' => 'Bacheca dei contenuti pianificati con motivi di blocco e rilancio in un clic.',
        'editorial_queue_empty' => 'Nessun articolo pianificato in coda.',
        'retry' => 'Riprova',
        'retry_bulk' => 'Riprova selezione',
        'retry_blocked_missing_fields' => 'Riprova bloccata: completa prima titolo e contenuto.',
        'retry_applied' => 'Riprova applicata.',
        'retry_applied_published' => 'Riprova applicata: articolo pubblicato.',
        'retry_applied_rescheduled' => 'Riprova applicata: pianificazione reimpostata (+1h).',
        'retry_bulk_summary' => 'Riprova massiva: %d pubblicati, %d ripianificati, %d ritentati, %d bloccati.',
        'blocked_reasons' => 'Motivi di blocco',
    ],
    'ja' => [
        'editorial_queue' => '編集キュー',
        'editorial_queue_help' => '予約コンテンツのボード。ブロック理由とワンクリック再実行。',
        'editorial_queue_empty' => 'キューに予約記事はありません。',
        'retry' => '再試行',
        'retry_bulk' => '選択を再試行',
        'retry_blocked_missing_fields' => '再試行できません。先にタイトルと本文を入力してください。',
        'retry_applied' => '再試行を実行しました。',
        'retry_applied_published' => '再試行を実行しました。記事を公開しました。',
        'retry_applied_rescheduled' => '再試行を実行しました。予定を再設定しました（+1h）。',
        'retry_bulk_summary' => '一括再試行: 公開 %d、再予定 %d、再試行 %d、ブロック %d。',
        'blocked_reasons' => 'ブロック理由',
    ],
    'nl' => [
        'editorial_queue' => 'Redactionele wachtrij',
        'editorial_queue_help' => 'Bord met geplande inhoud, blokkeringsredenen en herstart met een klik.',
        'editorial_queue_empty' => 'Geen geplande artikelen in de wachtrij.',
        'retry' => 'Opnieuw proberen',
        'retry_bulk' => 'Selectie opnieuw proberen',
        'retry_blocked_missing_fields' => 'Opnieuw proberen geblokkeerd: vul eerst titel en inhoud in.',
        'retry_applied' => 'Opnieuw proberen uitgevoerd.',
        'retry_applied_published' => 'Opnieuw proberen uitgevoerd: artikel gepubliceerd.',
        'retry_applied_rescheduled' => 'Opnieuw proberen uitgevoerd: planning opnieuw ingesteld (+1h).',
        'retry_bulk_summary' => 'Bulk opnieuw proberen: %d gepubliceerd, %d opnieuw gepland, %d opnieuw geprobeerd, %d geblokkeerd.',
        'blocked_reasons' => 'Blokkeringsredenen',
    ],
    'pt' => [
        'editorial_queue' => 'Fila editorial',
        'editorial_queue_help' => 'Quadro de conteudos agendados com motivos de bloqueio e nova tentativa em um clique.',
        'editorial_queue_empty' => 'Nenhum artigo agendado na fila.',
        'retry' => 'Tentar novamente',
        'retry_bulk' => 'Tentar novamente selecao',
        'retry_blocked_missing_fields' => 'Nova tentativa bloqueada: complete primeiro titulo e conteudo.',
        'retry_applied' => 'Nova tentativa aplicada.',
        'retry_applied_published' => 'Nova tentativa aplicada: artigo publicado.',
        'retry_applied_rescheduled' => 'Nova tentativa aplicada: agendamento redefinido (+1h).',
        'retry_bulk_summary' => 'Nova tentativa em lote: %d publicados, %d reagendados, %d retomados, %d bloqueados.',
        'blocked_reasons' => 'Motivos de bloqueio',
    ],
    'ru' => [
        'editorial_queue' => 'Редакционная очередь',
        'editorial_queue_help' => 'Доска запланированного контента с причинами блокировки и повтором в один клик.',
        'editorial_queue_empty' => 'В очереди нет запланированных статей.',
        'retry' => 'Повторить',
        'retry_bulk' => 'Повторить выбранное',
        'retry_blocked_missing_fields' => 'Повтор заблокирован: сначала заполните заголовок и контент.',
        'retry_applied' => 'Повтор выполнен.',
        'retry_applied_published' => 'Повтор выполнен: статья опубликована.',
        'retry_applied_rescheduled' => 'Повтор выполнен: расписание сброшено (+1h).',
        'retry_bulk_summary' => 'Групповой повтор: опубликовано %d, перепланировано %d, повторено %d, заблокировано %d.',
        'blocked_reasons' => 'Причины блокировки',
    ],
    'zh' => [
        'editorial_queue' => '编辑队列',
        'editorial_queue_help' => '已排期内容看板，含阻塞原因与一键重试。',
        'editorial_queue_empty' => '队列中暂无已排期文章。',
        'retry' => '重试',
        'retry_bulk' => '重试所选',
        'retry_blocked_missing_fields' => '重试被阻止：请先完善标题和内容。',
        'retry_applied' => '已执行重试。',
        'retry_applied_published' => '已执行重试：文章已发布。',
        'retry_applied_rescheduled' => '已执行重试：排期已重置 (+1h).',
        'retry_bulk_summary' => '批量重试：已发布 %d，已重排 %d，已重试 %d，已阻塞 %d。',
        'blocked_reasons' => '阻塞原因',
    ],
];

$adminLibrary = [
    'ar' => ['tag_cleanup_title' => 'تنظيف الوسوم', 'tag_cleanup_help' => 'توحيد متغيرات الوسوم المكررة قبل المراجعة أو التصدير.', 'tag_from_ph' => 'من وسم', 'tag_to_ph' => 'إلى وسم قياسي', 'tag_duplicates_empty' => 'لا توجد متغيرات وسوم مكررة.', 'ok_tags_merged' => 'تم دمج الوسوم في %d مستند.', 'ingestion_template' => 'قالب الإدخال', 'ingestion_template_none' => 'بدون', 'ingestion_template_training' => 'تدريب', 'ingestion_template_safety' => 'سلامة', 'ingestion_template_technical' => 'تقني', 'ingestion_template_legal' => 'قانوني'],
    'bn' => ['tag_cleanup_title' => 'ট্যাগ পরিষ্কার', 'tag_cleanup_help' => 'মডারেশন বা এক্সপোর্টের আগে ডুপ্লিকেট ট্যাগ ভ্যারিয়েন্ট একীভূত করুন।', 'tag_from_ph' => 'উৎস ট্যাগ', 'tag_to_ph' => 'ক্যানোনিক্যাল ট্যাগ', 'tag_duplicates_empty' => 'কোনো ডুপ্লিকেট ট্যাগ ভ্যারিয়েন্ট নেই।', 'ok_tags_merged' => '%dটি নথিতে ট্যাগ একীভূত হয়েছে।', 'ingestion_template' => 'ইনজেশন টেমপ্লেট', 'ingestion_template_none' => 'কোনোটি নয়', 'ingestion_template_training' => 'প্রশিক্ষণ', 'ingestion_template_safety' => 'নিরাপত্তা', 'ingestion_template_technical' => 'প্রযুক্তিগত', 'ingestion_template_legal' => 'আইনি'],
    'de' => ['tag_cleanup_title' => 'Tag-Bereinigung', 'tag_cleanup_help' => 'Doppelte Tag-Varianten vor Moderation oder Export normalisieren.', 'tag_from_ph' => 'Von Tag-Variante', 'tag_to_ph' => 'Zu kanonischem Tag', 'tag_duplicates_empty' => 'Keine doppelten Tag-Varianten gefunden.', 'ok_tags_merged' => 'Tags in %d Dokument(en) zusammengeführt.', 'ingestion_template' => 'Importvorlage', 'ingestion_template_none' => 'Keine', 'ingestion_template_training' => 'Schulung', 'ingestion_template_safety' => 'Sicherheit', 'ingestion_template_technical' => 'Technisch', 'ingestion_template_legal' => 'Rechtlich'],
    'en' => ['tag_cleanup_title' => 'Tag cleanup', 'tag_cleanup_help' => 'Normalize duplicated tag variants before moderation or export workflows.', 'tag_from_ph' => 'From tag variant', 'tag_to_ph' => 'To canonical tag', 'tag_duplicates_empty' => 'No duplicate tag variants detected.', 'ok_tags_merged' => 'Tags merged on %d document(s).', 'ingestion_template' => 'Ingestion template', 'ingestion_template_none' => 'None', 'ingestion_template_training' => 'Training', 'ingestion_template_safety' => 'Safety', 'ingestion_template_technical' => 'Technical', 'ingestion_template_legal' => 'Legal'],
    'es' => ['tag_cleanup_title' => 'Limpieza de etiquetas', 'tag_cleanup_help' => 'Normalice variantes duplicadas de etiquetas antes de moderación o exportación.', 'tag_from_ph' => 'Desde etiqueta', 'tag_to_ph' => 'A etiqueta canónica', 'tag_duplicates_empty' => 'No se detectaron variantes duplicadas de etiquetas.', 'ok_tags_merged' => 'Etiquetas fusionadas en %d documento(s).', 'ingestion_template' => 'Plantilla de ingesta', 'ingestion_template_none' => 'Ninguna', 'ingestion_template_training' => 'Formación', 'ingestion_template_safety' => 'Seguridad', 'ingestion_template_technical' => 'Técnico', 'ingestion_template_legal' => 'Legal'],
    'fr' => ['tag_cleanup_title' => 'Nettoyage des tags', 'tag_cleanup_help' => 'Normalisez les variantes de tags dupliquees avant moderation ou export.', 'tag_from_ph' => 'Tag source', 'tag_to_ph' => 'Tag canonique', 'tag_duplicates_empty' => 'Aucune variante de tag dupliquee detectee.', 'ok_tags_merged' => 'Tags fusionnes sur %d document(s).', 'ingestion_template' => 'Modele d ingestion', 'ingestion_template_none' => 'Aucun', 'ingestion_template_training' => 'Formation', 'ingestion_template_safety' => 'Securite', 'ingestion_template_technical' => 'Technique', 'ingestion_template_legal' => 'Juridique'],
    'hi' => ['tag_cleanup_title' => 'टैग सफाई', 'tag_cleanup_help' => 'मॉडरेशन या एक्सपोर्ट से पहले डुप्लिकेट टैग रूपों को सामान्य करें।', 'tag_from_ph' => 'किस टैग से', 'tag_to_ph' => 'किस मानक टैग में', 'tag_duplicates_empty' => 'कोई डुप्लिकेट टैग रूप नहीं मिला।', 'ok_tags_merged' => '%d दस्तावेज़ में टैग मर्ज किए गए।', 'ingestion_template' => 'इनजेशन टेम्पलेट', 'ingestion_template_none' => 'कोई नहीं', 'ingestion_template_training' => 'प्रशिक्षण', 'ingestion_template_safety' => 'सुरक्षा', 'ingestion_template_technical' => 'तकनीकी', 'ingestion_template_legal' => 'कानूनी'],
    'id' => ['tag_cleanup_title' => 'Pembersihan tag', 'tag_cleanup_help' => 'Normalkan varian tag duplikat sebelum moderasi atau ekspor.', 'tag_from_ph' => 'Dari tag', 'tag_to_ph' => 'Ke tag kanonik', 'tag_duplicates_empty' => 'Tidak ada varian tag duplikat.', 'ok_tags_merged' => 'Tag digabung pada %d dokumen.', 'ingestion_template' => 'Template ingesti', 'ingestion_template_none' => 'Tidak ada', 'ingestion_template_training' => 'Pelatihan', 'ingestion_template_safety' => 'Keamanan', 'ingestion_template_technical' => 'Teknis', 'ingestion_template_legal' => 'Legal'],
    'it' => ['tag_cleanup_title' => 'Pulizia tag', 'tag_cleanup_help' => 'Normalizza le varianti duplicate dei tag prima di moderazione o esportazione.', 'tag_from_ph' => 'Da tag variante', 'tag_to_ph' => 'A tag canonico', 'tag_duplicates_empty' => 'Nessuna variante duplicata rilevata.', 'ok_tags_merged' => 'Tag uniti su %d documento/i.', 'ingestion_template' => 'Modello di ingestione', 'ingestion_template_none' => 'Nessuno', 'ingestion_template_training' => 'Formazione', 'ingestion_template_safety' => 'Sicurezza', 'ingestion_template_technical' => 'Tecnico', 'ingestion_template_legal' => 'Legale'],
    'ja' => ['tag_cleanup_title' => 'タグ整理', 'tag_cleanup_help' => 'モデレーションやエクスポート前に重複タグを正規化します。', 'tag_from_ph' => '変換元タグ', 'tag_to_ph' => '変換先タグ', 'tag_duplicates_empty' => '重複タグは検出されませんでした。', 'ok_tags_merged' => '%d 件のドキュメントでタグを統合しました。', 'ingestion_template' => '取込テンプレート', 'ingestion_template_none' => 'なし', 'ingestion_template_training' => 'トレーニング', 'ingestion_template_safety' => '安全', 'ingestion_template_technical' => '技術', 'ingestion_template_legal' => '法務'],
    'nl' => ['tag_cleanup_title' => 'Tag-opruiming', 'tag_cleanup_help' => 'Normaliseer dubbele tagvarianten vóór moderatie of export.', 'tag_from_ph' => 'Van tagvariant', 'tag_to_ph' => 'Naar canonieke tag', 'tag_duplicates_empty' => 'Geen dubbele tagvarianten gevonden.', 'ok_tags_merged' => 'Tags samengevoegd in %d document(en).', 'ingestion_template' => 'Inname-sjabloon', 'ingestion_template_none' => 'Geen', 'ingestion_template_training' => 'Training', 'ingestion_template_safety' => 'Veiligheid', 'ingestion_template_technical' => 'Technisch', 'ingestion_template_legal' => 'Juridisch'],
    'pt' => ['tag_cleanup_title' => 'Limpeza de tags', 'tag_cleanup_help' => 'Normalize variantes duplicadas de tags antes de moderação ou exportação.', 'tag_from_ph' => 'Tag de origem', 'tag_to_ph' => 'Tag canônica', 'tag_duplicates_empty' => 'Nenhuma variante duplicada de tag detectada.', 'ok_tags_merged' => 'Tags mescladas em %d documento(s).', 'ingestion_template' => 'Modelo de ingestao', 'ingestion_template_none' => 'Nenhum', 'ingestion_template_training' => 'Treinamento', 'ingestion_template_safety' => 'Seguranca', 'ingestion_template_technical' => 'Tecnico', 'ingestion_template_legal' => 'Legal'],
    'ru' => ['tag_cleanup_title' => 'Очистка тегов', 'tag_cleanup_help' => 'Нормализуйте дубли тегов перед модерацией или экспортом.', 'tag_from_ph' => 'Из варианта тега', 'tag_to_ph' => 'В канонический тег', 'tag_duplicates_empty' => 'Дубликаты вариантов тегов не найдены.', 'ok_tags_merged' => 'Теги объединены в %d документ(ах).', 'ingestion_template' => 'Шаблон загрузки', 'ingestion_template_none' => 'Нет', 'ingestion_template_training' => 'Обучение', 'ingestion_template_safety' => 'Безопасность', 'ingestion_template_technical' => 'Технический', 'ingestion_template_legal' => 'Юридический'],
    'zh' => ['tag_cleanup_title' => '标签清理', 'tag_cleanup_help' => '在审核或导出前规范化重复标签变体。', 'tag_from_ph' => '来源标签', 'tag_to_ph' => '目标规范标签', 'tag_duplicates_empty' => '未检测到重复标签变体。', 'ok_tags_merged' => '已在 %d 个文档中合并标签。', 'ingestion_template' => '导入模板', 'ingestion_template_none' => '无', 'ingestion_template_training' => '培训', 'ingestion_template_safety' => '安全', 'ingestion_template_technical' => '技术', 'ingestion_template_legal' => '法律'],
];

/**
 * @param array<string,array<string,string>> $translations
 */
function applyTranslations(string $base, array $translations): void
{
    foreach ($translations as $locale => $pairs) {
        $path = $base . '/' . $locale . '.php';
        if (!is_file($path)) {
            continue;
        }
        $data = require $path;
        if (!is_array($data)) {
            continue;
        }
        foreach ($pairs as $key => $value) {
            $data[$key] = $value;
        }
        $out = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($path, $out);
    }
}

applyTranslations($root . '/admin_articles', $adminArticles);
applyTranslations($root . '/admin_library', $adminLibrary);

echo "admin articles/library translations updated\n";
