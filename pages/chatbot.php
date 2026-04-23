<?php
declare(strict_types=1);

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
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('chatbot');
    }
}

ob_start();
?>
<div class="card narrow">
    <h1>Raymond vous répond</h1>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label>Question
            <textarea name="question" rows="5" placeholder="Ex.: Comment exporter une QSL ou où trouver les plans de bandes ?"><?= e($question) ?></textarea>
        </label>
        <button class="button">Demander</button>
    </form>
    <?php if ($response): ?>
        <section class="inner-card">
            <h2>Réponse</h2>
            <p><?= nl2br(e((string) $response['answer'])) ?></p>
            <p class="help">Source : <?= e((string) $response['source']) ?></p>
        </section>
    <?php endif; ?>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Raymond vous répond');
