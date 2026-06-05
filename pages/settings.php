<?php
declare(strict_types=1);
$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('settings', $locale);
$rt = i18n_domain_translator('dashboard', $locale);
$newsletterT = i18n_domain_translator('newsletter', $locale);
$userId = (int) ($user['id'] ?? 0);
newsletter_ensure_tables();
$memberEmail = newsletter_normalize_email((string) ($user['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_newsletter') {
    try {
        verify_csrf();
        $newsletterAction = (string) ($_POST['newsletter_action'] ?? '');
        if ($newsletterAction === 'subscribe') {
            $email = newsletter_normalize_email((string) ($_POST['email'] ?? $memberEmail));
            if ($email === '') {
                throw new RuntimeException($newsletterT('err_invalid_email'));
            }
            if ((string) ($_POST['newsletter_consent'] ?? '') !== '1') {
                throw new RuntimeException($newsletterT('consent_required'));
            }
            newsletter_upsert_subscriber($email, $userId, 'member_settings', true, $newsletterT('consent_proof_member'));
            set_flash('success', $newsletterT('ok_subscribed'));
        } elseif ($newsletterAction === 'unsubscribe') {
            $currentNewsletter = newsletter_subscriber_for_member($userId);
            if ($currentNewsletter === null) {
                throw new RuntimeException($newsletterT('err_no_sub'));
            }
            newsletter_set_subscriber_status((int) $currentNewsletter['id'], 'unsubscribed');
            set_flash('success', $newsletterT('ok_unsubscribed'));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect_url(route_url('settings'));
}
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
$currentNewsletter = newsletter_subscriber_for_member($userId);
$newsletterSubscribed = $currentNewsletter !== null && (string) ($currentNewsletter['status'] ?? '') === 'active';
$newsletterEmail = newsletter_normalize_email((string) ($currentNewsletter['email'] ?? $memberEmail));

$pageTitle = (string) ($t['title'] ?? 'Account settings');
ob_start();
?>
<section class="card settings-module settings-preferences">
  <section class="settings-preferences-panel settings-preferences-newsletter" aria-labelledby="settings-newsletter-title">
    <h2 id="settings-newsletter-title" style="margin-top:0;"><?= e($newsletterT('title')) ?></h2>
    <p><?= e($newsletterT('intro')) ?></p>
    <?php if ($newsletterSubscribed): ?>
      <p><strong><?= e($newsletterT('status')) ?></strong> <?= e($newsletterT('subscribed')) ?> (<?= e($newsletterEmail) ?>)</p>
      <form method="post" class="inline-form" style="margin:.25rem 0 0;">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="toggle_newsletter">
        <input type="hidden" name="newsletter_action" value="unsubscribe">
        <button class="button danger small" type="submit"><?= e($newsletterT('unsubscribe')) ?></button>
      </form>
    <?php else: ?>
      <p><strong><?= e($newsletterT('status')) ?></strong> <?= e($newsletterT('not_subscribed')) ?></p>
      <form method="post" class="inline-form" style="margin:.25rem 0 0;gap:.7rem;align-items:flex-end;">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="toggle_newsletter">
        <input type="hidden" name="newsletter_action" value="subscribe">
        <label style="display:grid;gap:.25rem;">
          <span><?= e($newsletterT('email_label')) ?></span>
          <input type="email" name="email" value="<?= e($newsletterEmail) ?>" required>
        </label>
        <label style="display:flex;align-items:center;gap:.45rem;max-width:34rem;">
          <input type="checkbox" name="newsletter_consent" value="1" required>
          <span><?= e($newsletterT('consent_label')) ?></span>
        </label>
        <button class="button small" type="submit"><?= e($newsletterT('subscribe')) ?></button>
      </form>
      <?= privacy_notice_short_html('newsletter') ?>
    <?php endif; ?>
  </section>

  <section class="settings-preferences-panel settings-preferences-recommendations" aria-labelledby="settings-recommendations-title">
    <h2 id="settings-recommendations-title" style="margin-top:0;"><?= e($rt('recommendations_title')) ?></h2>
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
</section>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
