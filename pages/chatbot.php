<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_translator('chatbot', $locale);
set_page_meta(['title' => $t('meta_title'), 'description' => $t('meta_desc'), 'schema_type' => 'WebPage']);

if (!isset($_SESSION['chatbot_history']) || !is_array($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history'] = [];
}

$question = '';
$response = null;
$maxQuestionLength = 800;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'ask');
        if ($action === 'clear') {
            $_SESSION['chatbot_history'] = [];
            redirect('chatbot');
        }

        $question = trim((string) ($_POST['question'] ?? ''));
        $question = preg_replace('/\s+/u', ' ', $question) ?? $question;
        if ($question === '') {
            throw new RuntimeException($t('err_question'));
        }
        if (mb_strlen($question) > $maxQuestionLength) {
            $question = mb_substr($question, 0, $maxQuestionLength);
        }

        $response = answer_question_from_knowledge($question);
        try {
            if (table_exists('chatbot_logs')) {
                db()->prepare('INSERT INTO chatbot_logs (member_id, question, answer, source_name) VALUES (?, ?, ?, ?)')->execute([
                    current_user()['id'] ?? null,
                    $question,
                    $response['answer'],
                    $response['source'],
                ]);
            }
        } catch (Throwable) {
        }

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

$history = $_SESSION['chatbot_history'];
$historyCount = count($history);

ob_start();
?>
<div class="chatbot-shell">
    <aside class="chatbot-sidebar card">
        <img class="chatbot-illustration" src="<?= e(asset_url('assets/chartbot/chatbot.png')) ?>" alt="<?= e($t('chatbot_alt')) ?>">
        <h1><?= e($t('meta_title')) ?></h1>
        <p class="help"><?= e($t('sidebar_help')) ?></p>
        <div class="chatbot-status">
            <span class="badge muted"><?= $historyCount ?> <?= e($t('history_count')) ?></span>
            <span class="badge muted"><?= e($t('rag_ready')) ?></span>
        </div>
        <ul class="chatbot-suggestions" id="chatbot-suggestions">
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s1_q')) ?>"><?= e($t('s1_l')) ?></button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s2_q')) ?>"><?= e($t('s2_l')) ?></button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s3_q')) ?>"><?= e($t('s3_l')) ?></button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="<?= e($t('s4_q')) ?>"><?= e($t('s4_l')) ?></button></li>
        </ul>
        <form method="post" onsubmit="return confirm('<?= e($t('clear_confirm')) ?>');">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="clear">
            <button class="button ghost" type="submit"><?= e($t('clear')) ?></button>
        </form>
    </aside>

    <section class="chatbot-main card" aria-label="<?= e($t('conversation_label')) ?>">
        <div class="chatbot-thread" id="chatbot-thread">
            <?php if ($history === []): ?>
                <article class="chatbot-message bot">
                    <strong><?= e($t('welcome_title')) ?></strong>
                    <p><?= e($t('welcome')) ?></p>
                </article>
            <?php else: ?>
                <?php foreach ($history as $index => $item): ?>
                    <article class="chatbot-message user">
                        <p><?= e((string) $item['question']) ?></p>
                        <button type="button" class="chatbot-message-action" data-suggestion="<?= e((string) $item['question']) ?>"><?= e($t('reuse')) ?></button>
                    </article>
                    <article class="chatbot-message bot">
                        <p><?= nl2br(e((string) $item['answer'])) ?></p>
                        <button type="button" class="chatbot-message-action" data-copy-target="chatbot-answer-<?= (int) $index ?>"><?= e($t('copy')) ?></button>
                        <span id="chatbot-answer-<?= (int) $index ?>" hidden><?= e((string) $item['answer']) ?></span>
                        <p class="help"><?= e($t('source')) ?> <?= e((string) $item['source']) ?> · <?= e((string) $item['at']) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post" class="chatbot-form" id="chatbot-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="ask">
            <label for="chatbot-question" class="sr-only"><?= e($t('question_label')) ?></label>
            <textarea id="chatbot-question" name="question" rows="3" maxlength="<?= $maxQuestionLength ?>" placeholder="<?= e($t('placeholder')) ?>" data-wysiwyg="off" required><?= e($question) ?></textarea>
            <div class="chatbot-form-actions">
                <span class="help"><?= e($t('kbd_help')) ?> · <span id="chatbot-counter">0</span>/<?= $maxQuestionLength ?></span>
                <button class="button" type="submit"><?= e($t('send')) ?></button>
            </div>
        </form>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('meta_title'));
