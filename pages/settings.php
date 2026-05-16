<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titleMap = ['fr'=>'Paramètres du compte','en'=>'Account settings','de'=>'Kontoeinstellungen','es'=>'Configuración de la cuenta','it'=>'Impostazioni account','pt'=>'Definições da conta','nl'=>'Accountinstellingen','ar'=>'إعدادات الحساب','hi'=>'खाता सेटिंग्स','ja'=>'アカウント設定','zh'=>'账户设置','bn'=>'অ্যাকাউন্ট সেটিংস','ru'=>'Настройки аккаунта','id'=>'Pengaturan akun'];
$introMap = ['fr'=>'Centralisez ici vos préférences de compte et options d’interface.','en'=>'Manage your account preferences and interface options here.','de'=>'Verwalten Sie hier Ihre Kontoeinstellungen und Oberflächenoptionen.','es'=>'Gestiona aquí tus preferencias de cuenta y opciones de interfaz.','it'=>'Gestisci qui le preferenze dell\'account e le opzioni dell\'interfaccia.','pt'=>'Gira aqui as preferências da conta e opções da interface.','nl'=>'Beheer hier je accountvoorkeuren en interface-opties.','ar'=>'قم بإدارة تفضيلات حسابك وخيارات الواجهة من هنا.','hi'=>'यहाँ अपने खाते की प्राथमिकताएँ और इंटरफ़ेस विकल्प प्रबंधित करें।','ja'=>'ここでアカウント設定とインターフェースオプションを管理します。','zh'=>'在此管理您的账户偏好和界面选项。','bn'=>'এখানে আপনার অ্যাকাউন্ট পছন্দ এবং ইন্টারফেস অপশনগুলো পরিচালনা করুন।','ru'=>'Управляйте настройками аккаунта и интерфейса здесь.','id'=>'Kelola preferensi akun dan opsi antarmuka Anda di sini.'];
$links = [
    ['route' => 'code_q', 'label' => ['fr' => 'Code Q', 'en' => 'Q-code', 'de' => 'Q-Code', 'es' => 'Código Q', 'it' => 'Codice Q', 'pt' => 'Código Q', 'nl' => 'Q-code', 'ar' => 'رموز Q', 'hi' => 'Q-कोड', 'ja' => 'Qコード', 'zh' => 'Q代码', 'bn' => 'Q-কোড', 'ru' => 'Q-код', 'id' => 'Kode Q']],
    ['route' => 'code_cw', 'label' => ['fr' => 'Code CW', 'en' => 'CW code', 'de' => 'CW-Code', 'es' => 'Código CW', 'it' => 'Codice CW', 'pt' => 'Código CW', 'nl' => 'CW-code', 'ar' => 'شفرة CW', 'hi' => 'CW कोड', 'ja' => 'CWコード', 'zh' => 'CW代码', 'bn' => 'CW কোড', 'ru' => 'Код CW', 'id' => 'Kode CW']],
    ['route' => 'bandplan_on3', 'label' => ['fr' => 'Band plan ON3', 'en' => 'ON3 band plan', 'de' => 'ON3-Bandplan', 'es' => 'Plan de bandas ON3', 'it' => 'Band plan ON3', 'pt' => 'Plano de bandas ON3', 'nl' => 'ON3-bandplan', 'ar' => 'خطة نطاق ON3', 'hi' => 'ON3 बैंड प्लान', 'ja' => 'ON3 バンドプラン', 'zh' => 'ON3 频段规划', 'bn' => 'ON3 ব্যান্ড প্ল্যান', 'ru' => 'План диапазонов ON3', 'id' => 'Rencana band ON3']],
    ['route' => 'bandplan_on2', 'label' => ['fr' => 'Band plan ON2', 'en' => 'ON2 band plan', 'de' => 'ON2-Bandplan', 'es' => 'Plan de bandas ON2', 'it' => 'Band plan ON2', 'pt' => 'Plano de bandas ON2', 'nl' => 'ON2-bandplan', 'ar' => 'خطة نطاق ON2', 'hi' => 'ON2 बैंड प्लान', 'ja' => 'ON2 バンドプラン', 'zh' => 'ON2 频段规划', 'bn' => 'ON2 ব্যান্ড প্ল্যান', 'ru' => 'План диапазонов ON2', 'id' => 'Rencana band ON2']],
    ['route' => 'bandplan_harec', 'label' => ['fr' => 'Band plan HAREC', 'en' => 'HAREC band plan', 'de' => 'HAREC-Bandplan', 'es' => 'Plan de bandas HAREC', 'it' => 'Band plan HAREC', 'pt' => 'Plano de bandas HAREC', 'nl' => 'HAREC-bandplan', 'ar' => 'خطة نطاق HAREC', 'hi' => 'HAREC बैंड प्लान', 'ja' => 'HAREC バンドプラン', 'zh' => 'HAREC 频段规划', 'bn' => 'HAREC ব্যান্ড প্ল্যান', 'ru' => 'План диапазонов HAREC', 'id' => 'Rencana band HAREC']],
];
$pageTitle = $titleMap[$locale] ?? $titleMap['fr'];
ob_start();
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e($introMap[$locale] ?? $introMap['fr']) ?></p>
  <ul>
    <?php foreach ($links as $link): ?>
      <li><a href="<?= e(route_url((string) $link['route'])) ?>"><?= e((string) ($link['label'][$locale] ?? $link['label']['fr'])) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
