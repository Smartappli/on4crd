<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_domain_messages('chatbot');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
set_page_meta(['title' => $t('meta_title'), 'description' => $t('meta_desc'), 'schema_type' => 'WebPage']);

if (!isset($_SESSION['chatbot_history']) || !is_array($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history'] = [];
}

$question = '';
$response = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $question = trim((string) ($_POST['question'] ?? ''));
        if ($question === '') {
            throw new RuntimeException($t('err_question'));
        }

        $response = answer_question_from_knowledge($question);
        db()->prepare('INSERT INTO chatbot_logs (member_id, question, answer, source_name) VALUES (?, ?, ?, ?)')->execute([
            current_user()['id'] ?? null,
            $question,
            $response['answer'],
            $response['source'],
        ]);

        $_SESSION['chatbot_history'][] = [
            'question' => $question,
            'answer' => (string) $response['answer'],
            'source' => (string) $response['source'],
            'at' => date('Y-m-d H:i:s'),
        ];

        if (count($_SESSION['chatbot_history']) > 20) {
            $_SESSION['chatbot_history'] = array_slice($_SESSION['chatbot_history'], -20);
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('chatbot');
    }
}

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $_SESSION['chatbot_history'] = [];
    redirect('chatbot');
}

$history = $_SESSION['chatbot_history'];

ob_start();
?>
<div class="chatbot-shell">
    <aside class="chatbot-sidebar card">
        <img class="chatbot-illustration" src="<?= e(asset_url('assets/chartbot/chatbot.png')) ?>" alt="<?= e($t('chatbot_alt')) ?>">
        <h1>&nbsp;</h1>
        <p class="help"><?= e($t('sidebar_help')) ?></p>
        <ul class="chatbot-suggestions" id="chatbot-suggestions">
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s1_q')) ?>"><?= e($t('s1_l')) ?></button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s2_q')) ?>"><?= e($t('s2_l')) ?></button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s3_q')) ?>"><?= e($t('s3_l')) ?></button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s4_q')) ?>"><?= e($t('s4_l')) ?></button></li>
        </ul>
        <a class="button ghost" href="<?= e('index.php?route=chatbot&clear=1') ?>"><?= e($t('clear')) ?></a>
    </aside>

    <section class="chatbot-main card" aria-label="<?= e($t('conversation_label')) ?>">
        <div class="chatbot-thread" id="chatbot-thread">
            <?php if ($history === []): ?>
                <article class="chatbot-message bot">
                    <p><?= e($t('welcome')) ?></p>
                </article>
            <?php else: ?>
                <?php foreach ($history as $item): ?>
                    <article class="chatbot-message user">
                        <p><?= e((string) $item['question']) ?></p>
                    </article>
                    <article class="chatbot-message bot">
                        <p><?= nl2br(e((string) $item['answer'])) ?></p>
                        <p class="help"><?= e($t('source')) ?> <?= e((string) $item['source']) ?> · <?= e((string) $item['at']) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post" class="chatbot-form" id="chatbot-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label for="chatbot-question" class="sr-only"><?= e($t('question_label')) ?></label>
            <textarea id="chatbot-question" name="question" rows="3" placeholder="<?= e($t('placeholder')) ?>" data-wysiwyg="off" required><?= e($question) ?></textarea>
            <div class="chatbot-form-actions">
                <span class="help"><?= e($t('kbd_help')) ?></span>
                <button class="button" type="submit"><?= e($t('send')) ?></button>
            </div>
        </form>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('meta_title'));
