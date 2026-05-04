<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Assistant Raymond', 'meta_desc' => 'Posez vos questions techniques, réglementaires ou club à l’assistant ON4CRD.', 'err_question' => 'Veuillez saisir une question.', 'sidebar_help' => 'Posez une question technique, réglementaire ou club. Les réponses sont basées sur la base de connaissances interne.', 'chatbot_alt' => 'Illustration du chatbot Raymond', 'clear' => 'Effacer la conversation', 'conversation_label' => 'Conversation avec Raymond', 'welcome' => 'Bonjour 👋 Je suis Raymond. Posez votre question, je vous réponds immédiatement.', 'source' => 'Source :', 'question_label' => 'Question', 'placeholder' => 'Ex. : Où trouver les procédures QSL ?', 'kbd_help' => 'Entrée pour envoyer · Maj+Entrée pour un saut de ligne', 'send' => 'Envoyer'],
    'en' => ['meta_title' => 'Raymond assistant', 'meta_desc' => 'Ask technical, regulatory, or club-related questions to the ON4CRD assistant.', 'err_question' => 'Please enter a question.', 'sidebar_help' => 'Ask a technical, regulatory or club question. Answers are based on the internal knowledge base.', 'chatbot_alt' => 'Raymond chatbot illustration', 'clear' => 'Clear conversation', 'conversation_label' => 'Conversation with Raymond', 'welcome' => 'Hello 👋 I am Raymond. Ask your question and I will answer right away.', 'source' => 'Source:', 'question_label' => 'Question', 'placeholder' => 'E.g.: Where can I find QSL procedures?', 'kbd_help' => 'Enter to send · Shift+Enter for a newline', 'send' => 'Send'],
    'de' => ['meta_title' => 'Assistent Raymond', 'meta_desc' => 'Stellen Sie technische, regulatorische oder Clubfragen an den ON4CRD-Assistenten.', 'err_question' => 'Bitte geben Sie eine Frage ein.', 'sidebar_help' => 'Stellen Sie eine technische, regulatorische oder Clubfrage. Die Antworten basieren auf der internen Wissensdatenbank.', 'chatbot_alt' => 'Illustration des Raymond-Chatbots', 'clear' => 'Konversation löschen', 'conversation_label' => 'Gespräch mit Raymond', 'welcome' => 'Hallo 👋 Ich bin Raymond. Stellen Sie Ihre Frage, ich antworte sofort.', 'source' => 'Quelle:', 'question_label' => 'Frage', 'placeholder' => 'Bsp.: Wo finde ich QSL-Verfahren?', 'kbd_help' => 'Enter zum Senden · Shift+Enter für Zeilenumbruch', 'send' => 'Senden'],
    'nl' => ['meta_title' => 'Raymond-assistent', 'meta_desc' => 'Stel technische, reglementaire of clubvragen aan de ON4CRD-assistent.', 'err_question' => 'Voer een vraag in.', 'sidebar_help' => 'Stel een technische, reglementaire of clubvraag. Antwoorden zijn gebaseerd op de interne kennisbank.', 'chatbot_alt' => 'Illustratie van Raymond-chatbot', 'clear' => 'Gesprek wissen', 'conversation_label' => 'Gesprek met Raymond', 'welcome' => 'Hallo 👋 Ik ben Raymond. Stel je vraag, ik antwoord meteen.', 'source' => 'Bron:', 'question_label' => 'Vraag', 'placeholder' => 'Bijv.: Waar vind ik QSL-procedures?', 'kbd_help' => 'Enter om te verzenden · Shift+Enter voor een nieuwe regel', 'send' => 'Verzenden'],
];
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
            <li><button type="button" class="chatbot-chip" data-suggestion="Comment exporter une QSL ?">Exporter une QSL</button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="Quels sont les plans de bandes HF ?">Plans de bandes HF</button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="Comment rejoindre le club ?">Adhésion</button></li>
            <li><button type="button" class="chatbot-chip" data-suggestion="Comment publier un article sur le site ?">Publier un article</button></li>
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
            <textarea id="chatbot-question" name="question" rows="3" placeholder="<?= e($t('placeholder')) ?>" required><?= e($question) ?></textarea>
            <div class="chatbot-form-actions">
                <span class="help"><?= e($t('kbd_help')) ?></span>
                <button class="button" type="submit"><?= e($t('send')) ?></button>
            </div>
        </form>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('meta_title'));
