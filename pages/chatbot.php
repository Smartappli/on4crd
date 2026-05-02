<?php
declare(strict_types=1);

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
            throw new RuntimeException('Veuillez saisir une question.');
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
        <img class="chatbot-illustration" src="<?= e(asset_url('assets/chartbot/chatbot.png')) ?>" alt="Illustration du chatbot Raymond">
        <h1>Raymond vous répond</h1>
        <p class="help">Posez une question technique, réglementaire ou club. Les réponses sont basées sur la base de connaissances interne.</p>
        <ul class="chatbot-suggestions" id="chatbot-suggestions">
            <li><button type="button" class="chatbot-chip" data-suggestion="Comment exporter une QSL ?">Exporter une QSL</button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="Quels sont les plans de bandes HF ?">Plans de bandes HF</button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="Comment rejoindre le club ?">Adhésion</button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="Comment publier un article sur le site ?">Publier un article</button></li>
        </ul>
        <a class="button ghost" href="<?= e('index.php?route=chatbot&clear=1') ?>">Effacer la conversation</a>
    </aside>

    <section class="chatbot-main card" aria-label="Conversation avec Raymond">
        <div class="chatbot-thread" id="chatbot-thread">
            <?php if ($history === []): ?>
                <article class="chatbot-message bot">
                    <p>Bonjour 👋 Je suis Raymond. Posez votre question, je vous réponds immédiatement.</p>
                </article>
            <?php else: ?>
                <?php foreach ($history as $item): ?>
                    <article class="chatbot-message user">
                        <p><?= e((string) $item['question']) ?></p>
                    </article>
                    <article class="chatbot-message bot">
                        <p><?= nl2br(e((string) $item['answer'])) ?></p>
                        <p class="help">Source : <?= e((string) $item['source']) ?> · <?= e((string) $item['at']) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post" class="chatbot-form" id="chatbot-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label for="chatbot-question" class="sr-only">Question</label>
            <textarea id="chatbot-question" name="question" rows="3" placeholder="Ex. : Où trouver les procédures QSL ?" required><?= e($question) ?></textarea>
            <div class="chatbot-form-actions">
                <span class="help">Entrée pour envoyer · Maj+Entrée pour un saut de ligne</span>
                <button class="button" type="submit">Envoyer</button>
            </div>
        </form>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Raymond vous répond');
