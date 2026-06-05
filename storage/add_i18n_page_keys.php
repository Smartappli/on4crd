<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$locales = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
$domains = [
    'articles' => [
        'fr' => [
            'error_invalid_user' => 'Utilisateur invalide.',
            'error_wait_before_next' => 'Veuillez patienter une minute avant de proposer un autre article.',
            'error_daily_limit' => 'Limite atteinte : maximum 5 propositions d\'article par 24 heures.',
            'propose_article_meta_desc' => 'Composer et soumettre un article pour validation.',
            'module_unavailable' => 'Le module articles est temporairement indisponible.',
            'error_field_too_long' => 'Un des champs dépasse la longueur autorisée.',
            'error_title_content_required' => 'Le titre et le contenu sont obligatoires.',
            'error_content_empty_after_cleanup' => 'Le contenu de l\'article est vide après nettoyage.',
            'propose_article_success' => 'Article soumis pour validation.',
            'error_article_save_failed' => 'Impossible d\'enregistrer l\'article pour le moment.',
            'propose_article_help' => 'Mettez votre article en page avec des titres, paragraphes, listes et liens. Il sera enregistré dans vos contenus puis validé avant publication.',
            'my_contents' => 'Mes contenus',
            'article_title_label' => 'Titre de l\'article',
            'category_label' => 'Catégorie',
            'excerpt_label' => 'Résumé',
            'excerpt_placeholder' => 'Court résumé affiché dans la liste des articles.',
            'content_label' => 'Contenu mis en page',
            'content_placeholder' => '<h2>Titre de section</h2>' . "\n" . '<p>Votre texte...</p>' . "\n" . '<ul><li>Point important</li></ul>',
            'html_cleanup_help' => 'Le HTML est nettoyé automatiquement. Les scripts, iframes et attributs dangereux sont retirés avant validation.',
            'submit_for_review' => 'Soumettre pour validation',
        ],
        'en' => [
            'error_invalid_user' => 'Invalid user.',
            'error_wait_before_next' => 'Please wait one minute before proposing another article.',
            'error_daily_limit' => 'Limit reached: maximum 5 article proposals per 24 hours.',
            'propose_article_meta_desc' => 'Draft and submit an article for validation.',
            'module_unavailable' => 'The articles module is temporarily unavailable.',
            'error_field_too_long' => 'One of the fields exceeds the allowed length.',
            'error_title_content_required' => 'Title and content are required.',
            'error_content_empty_after_cleanup' => 'The article content is empty after cleanup.',
            'propose_article_success' => 'Article submitted for validation.',
            'error_article_save_failed' => 'Unable to save the article right now.',
            'propose_article_help' => 'Lay out your article with headings, paragraphs, lists and links. It will be saved in your content and validated before publication.',
            'my_contents' => 'My content',
            'article_title_label' => 'Article title',
            'category_label' => 'Category',
            'excerpt_label' => 'Summary',
            'excerpt_placeholder' => 'Short summary displayed in the article list.',
            'content_label' => 'Formatted content',
            'content_placeholder' => '<h2>Section title</h2>' . "\n" . '<p>Your text...</p>' . "\n" . '<ul><li>Important point</li></ul>',
            'html_cleanup_help' => 'HTML is cleaned automatically. Scripts, iframes and dangerous attributes are removed before validation.',
            'submit_for_review' => 'Submit for validation',
        ],
    ],
    'wiki' => [
        'fr' => [
            'themes' => 'Thématiques',
            'all_themes' => 'Toutes les thématiques',
            'propose_theme_subject' => 'Proposition de thématique wiki ON4CRD',
            'propose_theme' => 'Proposer une thématique',
            'propose_page' => 'Proposer une page',
            'propose_theme_intro' => 'Indiquez la thématique à ajouter et les pages qui devraient y être liées.',
            'close' => 'Fermer',
            'propose_theme_body_intro' => 'Proposition de thématique wiki :',
            'propose_theme_name' => 'Nom de la thématique',
            'propose_theme_reason' => 'Pourquoi l\'ajouter ?',
            'propose_theme_contact' => 'Votre contact',
            'propose_theme_submit' => 'Envoyer la proposition',
            'cancel' => 'Annuler',
        ],
        'en' => [
            'themes' => 'Themes',
            'all_themes' => 'All themes',
            'propose_theme_subject' => 'ON4CRD wiki theme proposal',
            'propose_theme' => 'Suggest a theme',
            'propose_page' => 'Suggest a page',
            'propose_theme_intro' => 'Describe the theme to add and the pages that should be linked to it.',
            'close' => 'Close',
            'propose_theme_body_intro' => 'Wiki theme proposal:',
            'propose_theme_name' => 'Theme name',
            'propose_theme_reason' => 'Why add it?',
            'propose_theme_contact' => 'Your contact',
            'propose_theme_submit' => 'Send proposal',
            'cancel' => 'Cancel',
        ],
    ],
    'wiki_edit' => [
        'fr' => [
            'propose_title' => 'Proposer une page wiki',
            'propose_meta_desc' => 'Créer une nouvelle page wiki depuis l’espace membre.',
            'error_title_content_required' => 'Le titre et le contenu sont obligatoires.',
            'error_field_too_long' => 'Un des champs dépasse la longueur autorisée.',
            'propose_success' => 'Page wiki proposée. Elle sera publiée après validation.',
            'wiki_label' => 'Wiki',
            'propose_help' => 'Rédigez une nouvelle page avec du HTML simple. Elle sera relue avant publication.',
            'propose_submit' => 'Proposer la page',
            'cancel' => 'Annuler',
        ],
        'en' => [
            'propose_title' => 'Suggest a wiki page',
            'propose_meta_desc' => 'Create a new wiki page from the member area.',
            'error_title_content_required' => 'Title and content are required.',
            'error_field_too_long' => 'One of the fields exceeds the allowed length.',
            'propose_success' => 'Wiki page submitted. It will be published after validation.',
            'wiki_label' => 'Wiki',
            'propose_help' => 'Write a new page with simple HTML. It will be reviewed before publication.',
            'propose_submit' => 'Submit page',
            'cancel' => 'Cancel',
        ],
    ],
];
$added = 0;
foreach ($domains as $domain => $localized) {
    foreach ($locales as $locale) {
        $path = $root . '/app/i18n/' . $domain . '/' . $locale . '.php';
        $values = require $path;
        $additions = $locale === 'fr' ? $localized['fr'] : ($locale === 'en' ? $localized['en'] : $localized['en']);
        foreach ($additions as $key => $value) {
            if (!array_key_exists($key, $values)) {
                $values[$key] = $value;
                $added++;
            }
        }
        file_put_contents($path, "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($values, true) . ";\n");
    }
}
echo "ADDED=$added\n";
