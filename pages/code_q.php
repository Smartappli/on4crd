<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('code_q', $locale);
$title = (string) $t['title'];
$codeLabel = (string) $t['code'];
$meaningLabel = (string) $t['meaning'];
$qRows = [];
foreach ($t['codes'] as $code => $meaning) {
    if (is_string($code) && is_string($meaning) && preg_match('/^Q[A-Z]{2}$/', $code) === 1) {
        $qRows[] = [$code, $meaning];
    }
}

ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e((string) $t['intro']) ?></p>
  <div class="table-wrap mt-3"><table><thead><tr><th><?= e($codeLabel) ?></th><th><?= e($meaningLabel) ?></th></tr></thead><tbody>
  <?php foreach ($qRows as $row): ?>
    <tr><td><?= e($row[0]) ?></td><td><?= e($row[1]) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
