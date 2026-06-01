<?php
declare(strict_types=1);
$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('settings', $locale);
$rt = i18n_domain_translator('dashboard', $locale);
$userId = (int) ($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_recommendations') {
    verify_csrf();
    $enabled = ((string) ($_POST['recommendations_enabled'] ?? '1')) === '1';
    set_member_preference_bool($userId, 'personalized_recommendations_enabled', $enabled);
    set_flash('success', $rt('recommendations_pref_saved'));
    redirect_url(route_url('settings'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_recommendation_signals') {
    verify_csrf();
    $signalKeys = ['article', 'wiki', 'classified', 'album', 'library'];
    foreach ($signalKeys as $signal) {
        $enabled = isset($_POST['signals'][$signal]) && (string) $_POST['signals'][$signal] === '1';
        set_member_preference_bool($userId, 'recommendations_signal_' . $signal . '_enabled', $enabled);
    }
    set_flash('success', $rt('recommendations_pref_saved'));
    redirect_url(route_url('settings'));
}

$recommendationsEnabled = member_preference_bool($userId, 'personalized_recommendations_enabled', true);
$recommendationSignals = [
    'article' => member_preference_bool($userId, 'recommendations_signal_article_enabled', true),
    'wiki' => member_preference_bool($userId, 'recommendations_signal_wiki_enabled', true),
    'classified' => member_preference_bool($userId, 'recommendations_signal_classified_enabled', true),
    'album' => member_preference_bool($userId, 'recommendations_signal_album_enabled', true),
    'library' => member_preference_bool($userId, 'recommendations_signal_library_enabled', true),
];
$recommendations = $recommendationsEnabled ? member_personalized_recommendations($userId, 6) : [];

$links = [
    ['route' => 'code_q', 'label_key' => 'link_code_q'],
    ['route' => 'code_cw', 'label_key' => 'link_code_cw'],
    ['route' => 'bandplan_on3', 'label_key' => 'link_bandplan_on3'],
    ['route' => 'bandplan_on2', 'label_key' => 'link_bandplan_on2'],
    ['route' => 'bandplan_harec', 'label_key' => 'link_bandplan_harec'],
];
$pageTitle = (string) ($t['title'] ?? 'Account settings');
ob_start();
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e((string) ($t['intro'] ?? 'Manage your account preferences and interface options here.')) ?></p>
  <ul>
    <?php foreach ($links as $link): ?>
      <li><a href="<?= e(route_url((string) $link['route'])) ?>"><?= e((string) ($t[(string) $link['label_key']] ?? $link['route'])) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<section class="card">
  <h2 style="margin-top:0;"><?= e($rt('recommendations_title')) ?></h2>
  <form method="post" class="inline-form" style="margin:.25rem 0 1rem;">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="toggle_recommendations">
    <label style="display:flex;align-items:center;gap:.45rem;">
      <input type="checkbox" name="recommendations_enabled" value="1" <?= $recommendationsEnabled ? 'checked' : '' ?>>
      <span><?= e($rt('recommendations_opt_in_label')) ?></span>
    </label>
    <button class="button secondary small" type="submit"><?= e($rt('save_layout')) ?></button>
  </form>
  <p class="help"><?= e($rt('recommendations_opt_in_help')) ?></p>
  <form method="post" class="inline-form" style="margin:.25rem 0 1rem;display:flex;flex-wrap:wrap;gap:.6rem 1rem;align-items:center;">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="toggle_recommendation_signals">
    <label style="display:flex;align-items:center;gap:.35rem;"><input type="checkbox" name="signals[article]" value="1" <?= $recommendationSignals['article'] ? 'checked' : '' ?>> <span><?= e($rt('signal_article')) ?></span></label>
    <label style="display:flex;align-items:center;gap:.35rem;"><input type="checkbox" name="signals[wiki]" value="1" <?= $recommendationSignals['wiki'] ? 'checked' : '' ?>> <span><?= e($rt('signal_wiki')) ?></span></label>
    <label style="display:flex;align-items:center;gap:.35rem;"><input type="checkbox" name="signals[classified]" value="1" <?= $recommendationSignals['classified'] ? 'checked' : '' ?>> <span><?= e($rt('signal_classified')) ?></span></label>
    <label style="display:flex;align-items:center;gap:.35rem;"><input type="checkbox" name="signals[album]" value="1" <?= $recommendationSignals['album'] ? 'checked' : '' ?>> <span><?= e($rt('signal_album')) ?></span></label>
    <label style="display:flex;align-items:center;gap:.35rem;"><input type="checkbox" name="signals[library]" value="1" <?= $recommendationSignals['library'] ? 'checked' : '' ?>> <span><?= e($rt('signal_library')) ?></span></label>
    <button class="button secondary small" type="submit"><?= e($rt('save_layout')) ?></button>
  </form>
  <?php if ($recommendations === []): ?>
    <p class="help"><?= e($rt('recommendations_empty')) ?></p>
  <?php else: ?>
    <ul class="stack" style="list-style:none;padding:0;margin:0;">
      <?php foreach ($recommendations as $item): ?>
        <?php $reasonKey = (string) ($item['reason_key'] ?? ''); ?>
        <li class="row-between" style="gap:.8rem;align-items:flex-start;">
          <span>
            <span><?= e((string) ($item['title'] ?? '')) ?></span><br>
            <small class="help"><?= e($rt('recommendations_why')) ?>: <?= e($rt($reasonKey !== '' ? $reasonKey : 'recommendation_reason_default')) ?></small>
          </span>
          <?php if (trim((string) ($item['url'] ?? '')) !== ''): ?><a class="button secondary small" href="<?= e((string) $item['url']) ?>"><?= e($rt('open')) ?></a><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
