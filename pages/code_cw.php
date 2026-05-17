<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code CW (Morse)','en'=>'CW code (Morse)','de'=>'CW-Code (Morse)','nl'=>'CW-code (Morse)','es'=>'Código CW (Morse)','it'=>'Codice CW (Morse)','pt'=>'Código CW (Morse)','ar'=>'شفرة CW (مورس)','hi'=>'CW कोड (मॉर्स)','ja'=>'CWコード（モールス）','zh'=>'CW代码（摩尔斯）','bn'=>'CW কোড (মোর্স)','ru'=>'Код CW (Морзе)','id'=>'Kode CW (Morse)'];
$intro = ['fr'=>'Tableau complet alphabet + chiffres + ponctuation utile.','en'=>'Complete table with alphabet, digits and useful punctuation.','de'=>'Vollständige Tabelle mit Alphabet, Zahlen und nützlichen Satzzeichen.','nl'=>'Volledige tabel met alfabet, cijfers en nuttige leestekens.','es'=>'Tabla completa con alfabeto, dígitos y puntuación útil.','it'=>'Tabella completa con alfabeto, cifre e punteggiatura utile.','pt'=>'Tabela completa com alfabeto, dígitos e pontuação útil.','ar'=>'جدول كامل يضم الأبجدية والأرقام وعلامات الترقيم المفيدة.','hi'=>'अक्षरों, अंकों और उपयोगी विरामचिह्नों की पूरी तालिका।','ja'=>'アルファベット・数字・便利な句読点を含む完全な表です。','zh'=>'包含字母、数字和常用标点的完整表格。','bn'=>'বর্ণমালা, সংখ্যা ও প্রয়োজনীয় বিরামচিহ্নসহ সম্পূর্ণ টেবিল।','ru'=>'Полная таблица с алфавитом, цифрами и полезными знаками пунктуации.','id'=>'Tabel lengkap dengan alfabet, angka, dan tanda baca yang berguna.'];
$charLabel = ['fr'=>'Caractère','en'=>'Character','de'=>'Zeichen','nl'=>'Teken','es'=>'Carácter','it'=>'Carattere','pt'=>'Caractere','ar'=>'الحرف','hi'=>'अक्षर','ja'=>'文字','zh'=>'字符','bn'=>'অক্ষর','ru'=>'Символ','id'=>'Karakter'];
$title = i18n_localized_value($titles, $locale, 'fr');
$rows = [
['A','.-','N','-.'],['B','-...','O','---'],['C','-.-.','P','.--.'],['D','-..','Q','--.-'],['E','.','R','.-.'],['F','..-.','S','...'],['G','--.','T','-'],['H','....','U','..-'],['I','..','V','...-'],['J','.---','W','.--'],['K','-.-','X','-..-'],['L','.-..','Y','-.--'],['M','--','Z','--..'],
['1','.----','6','-....'],['2','..---','7','--...'],['3','...--','8','---..'],['4','....-','9','----.'],['5','.....','0','-----'],
['.','.-.-.-',',','--..--'],['?','..--..','/','-..-.'],['=','-...-','+','.-.-.']
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e(i18n_localized_value($intro, $locale, 'fr')) ?></p>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th><?= e(i18n_localized_value($charLabel, $locale, 'fr')) ?></th><th>Code</th><th><?= e(i18n_localized_value($charLabel, $locale, 'fr')) ?></th><th>Code</th></tr></thead>
      <tbody><?php foreach ($rows as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= e($r[1]) ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
  <h2 class="mt-4"><?php $prosignTitle = ['fr'=>'Prosigns','en'=>'Prosigns','de'=>'Prosignale','nl'=>'Prosigns','es'=>'Prosignos','it'=>'Prosign','pt'=>'Prosignos','ar'=>'إشارات إجرائية','hi'=>'प्रोसाइन','ja'=>'プロサイン','zh'=>'程序信号','bn'=>'প্রোসাইন','ru'=>'Просигналы','id'=>'Prosign']; ?><?= e(i18n_localized_value($prosignTitle, $locale, 'fr')) ?></h2>
  <div class="table-wrap mt-2"><table><thead><tr><th>Prosign</th><th>Code</th><th><?php $usageMap = ['fr'=>'Usage','en'=>'Usage','de'=>'Verwendung','nl'=>'Gebruik','es'=>'Uso','it'=>'Uso','pt'=>'Uso','ar'=>'الاستخدام','hi'=>'उपयोग','ja'=>'用途','zh'=>'用途','bn'=>'ব্যবহার','ru'=>'Применение','id'=>'Penggunaan']; ?><?= e(i18n_localized_value($usageMap, $locale, 'fr')) ?></th></tr></thead><tbody><tr><td>AR</td><td>.-.-.</td><td><?php $map = ['fr'=>'Fin de message','en'=>'End of message','de'=>'Ende der Nachricht','nl'=>'Einde bericht','es'=>'Fin del mensaje','it'=>'Fine del messaggio','pt'=>'Fim da mensagem','ar'=>'نهاية الرسالة','hi'=>'संदेश का अंत','ja'=>'メッセージ終了','zh'=>'报文结束','bn'=>'বার্তার শেষ','ru'=>'Конец сообщения','id'=>'Akhir pesan']; ?><?= e(i18n_localized_value($map, $locale, 'fr')) ?></td></tr><tr><td>SK</td><td>...-.-</td><td><?php $map = ['fr'=>'Fin de contact','en'=>'End of contact','de'=>'Ende der Verbindung','nl'=>'Einde contact','es'=>'Fin del contacto','it'=>'Fine del contatto','pt'=>'Fim do contacto','ar'=>'نهاية الاتصال','hi'=>'संपर्क समाप्त','ja'=>'交信終了','zh'=>'联络结束','bn'=>'যোগাযোগের শেষ','ru'=>'Конец связи','id'=>'Akhir kontak']; ?><?= e(i18n_localized_value($map, $locale, 'fr')) ?></td></tr><tr><td>BT</td><td>-...-</td><td><?php $map = ['fr'=>'Séparateur','en'=>'Separator','de'=>'Trenner','nl'=>'Scheiding','es'=>'Separador','it'=>'Separatore','pt'=>'Separador','ar'=>'فاصل','hi'=>'विभाजक','ja'=>'区切り','zh'=>'分隔符','bn'=>'বিভাজক','ru'=>'Разделитель','id'=>'Pemisah']; ?><?= e(i18n_localized_value($map, $locale, 'fr')) ?></td></tr><tr><td>AS</td><td>.-...</td><td><?php $map = ['fr'=>'Attendez','en'=>'Wait','de'=>'Warten','nl'=>'Wacht','es'=>'Espere','it'=>'Attendere','pt'=>'Aguarde','ar'=>'انتظر','hi'=>'प्रतीक्षा करें','ja'=>'待機','zh'=>'等待','bn'=>'অপেক্ষা করুন','ru'=>'Ожидайте','id'=>'Tunggu']; ?><?= e(i18n_localized_value($map, $locale, 'fr')) ?></td></tr></tbody></table></div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
