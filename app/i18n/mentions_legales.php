<?php
declare(strict_types=1);

$messages = [];
$messages['fr'] = require_once __DIR__ . '/mentions_legales/fr.php';
$messages['en'] = require_once __DIR__ . '/mentions_legales/en.php';
$messages['de'] = require_once __DIR__ . '/mentions_legales/de.php';
$messages['nl'] = require_once __DIR__ . '/mentions_legales/nl.php';
$messages['it'] = require_once __DIR__ . '/mentions_legales/it.php';
$messages['es'] = require_once __DIR__ . '/mentions_legales/es.php';
$messages['pt'] = require_once __DIR__ . '/mentions_legales/pt.php';
$messages['bg'] = require_once __DIR__ . '/mentions_legales/bg.php';
$messages['hr'] = require_once __DIR__ . '/mentions_legales/hr.php';
$messages['cs'] = require_once __DIR__ . '/mentions_legales/cs.php';
$messages['da'] = require_once __DIR__ . '/mentions_legales/da.php';
$messages['et'] = require_once __DIR__ . '/mentions_legales/et.php';
$messages['fi'] = require_once __DIR__ . '/mentions_legales/fi.php';
$messages['el'] = require_once __DIR__ . '/mentions_legales/el.php';
$messages['hu'] = require_once __DIR__ . '/mentions_legales/hu.php';
$messages['ga'] = require_once __DIR__ . '/mentions_legales/ga.php';
$messages['lv'] = require_once __DIR__ . '/mentions_legales/lv.php';
$messages['lt'] = require_once __DIR__ . '/mentions_legales/lt.php';
$messages['mt'] = require_once __DIR__ . '/mentions_legales/mt.php';
$messages['pl'] = require_once __DIR__ . '/mentions_legales/pl.php';
$messages['ro'] = require_once __DIR__ . '/mentions_legales/ro.php';
$messages['sk'] = require_once __DIR__ . '/mentions_legales/sk.php';
$messages['sl'] = require_once __DIR__ . '/mentions_legales/sl.php';
$messages['sv'] = require_once __DIR__ . '/mentions_legales/sv.php';
$messages['ar'] = require_once __DIR__ . '/mentions_legales/ar.php';
$messages['hi'] = require_once __DIR__ . '/mentions_legales/hi.php';
$messages['ja'] = require_once __DIR__ . '/mentions_legales/ja.php';
$messages['zh'] = require_once __DIR__ . '/mentions_legales/zh.php';
$messages['bn'] = require_once __DIR__ . '/mentions_legales/bn.php';
$messages['ru'] = require_once __DIR__ . '/mentions_legales/ru.php';
$messages['id'] = require_once __DIR__ . '/mentions_legales/id.php';

$messages['fr'] = array_replace($messages['fr'] ?? [], [
    'title' => 'Mentions légales',
    'summary' => 'Cette page présente l\'éditeur du site ON4CRD, les informations de contact, les règles de propriété intellectuelle, les responsabilités et les références RGPD.',
    'updated_at_label' => 'Dernière mise à jour',
    'updated_at' => '05 juin 2026',
    'identity_title' => 'Éditeur et hébergement',
    'identity_editor' => 'Éditeur du site',
    'identity_address' => 'Adresse',
    'identity_contact' => 'Contact',
    'identity_publication_manager' => 'Responsable de publication',
    'identity_hosting' => 'Hébergement',
    'related_pages_title' => 'Pages associées',
    'terms_link_label' => 'Conditions générales d\'utilisation',
    'privacy_link_label' => 'Vie privée et RGPD',
    'sections' => [
        [
            'title' => '1. Éditeur du site',
            'body' => 'Le présent site est édité par {club_name}, radio club établi à l\'adresse suivante: {postal_address}. Le contact public du club est {contact_email}.',
        ],
        [
            'title' => '2. Responsable de publication',
            'body' => 'Le responsable de publication est {publication_manager}. Les demandes relatives au contenu publié, aux corrections, aux droits d\'auteur ou aux signalements peuvent être adressées à {contact_email}.',
        ],
        [
            'title' => '3. Hébergement',
            'body' => 'Le site est hébergé via {hosting_name}. Adresse ou référence d\'hébergement: {hosting_address}. Site de l\'hébergeur ou référence technique: {hosting_url}. Si l\'hébergeur réel diffère selon l\'environnement de déploiement, cette information doit être ajustée dans la configuration du site.',
        ],
        [
            'title' => '4. Nature du site',
            'body' => 'ON4CRD est un portail associatif radioamateur. Il publie des informations sur le club, des actualités, des événements, des articles, des ressources wiki, des albums, des outils techniques et des services liés aux membres. Les informations diffusées sont fournies dans un objectif documentaire, communautaire et organisationnel.',
        ],
        [
            'title' => '5. Propriété intellectuelle',
            'body' => 'Sauf mention contraire, l\'ensemble des contenus présents sur ce site, notamment textes, articles, photographies, documents, logos, éléments graphiques, bases de connaissances, structure et développements, appartient à {club_name}, à ses membres contributeurs ou à leurs titulaires respectifs.',
            'items' => [
                'Toute reproduction, représentation, modification, diffusion ou exploitation substantielle sans autorisation préalable est interdite, sauf exceptions légales.',
                'Les marques, logos, indicatifs, documents ou contenus de tiers restent la propriété de leurs titulaires.',
                'Les citations courtes et liens vers les pages publiques sont autorisés lorsqu\'ils mentionnent clairement la source ON4CRD.',
            ],
        ],
        [
            'title' => '6. Responsabilité éditoriale',
            'body' => 'Le club apporte un soin raisonnable à l\'exactitude des informations publiées. Des erreurs, omissions, changements d\'horaire, liens rompus ou informations devenues obsolètes peuvent toutefois subsister. ON4CRD ne peut être tenu responsable d\'une interprétation inexacte ou d\'un usage inadapté des informations publiées.',
        ],
        [
            'title' => '7. Informations radioamateurs',
            'body' => 'Les informations techniques, plans de bandes, outils de calcul, articles, retours d\'expérience et contenus wiki ne remplacent pas les textes officiels, les prescriptions du BIPT, les règles IARU applicables, les notices constructeurs ni les obligations propres à chaque opérateur radioamateur.',
        ],
        [
            'title' => '8. Liens hypertextes',
            'body' => 'Le site peut contenir des liens vers des sites tiers, cartes, documents administratifs, outils radioamateurs, réseaux sociaux ou partenaires. ON4CRD n\'exerce pas de contrôle permanent sur ces ressources externes et ne peut garantir leur contenu, leur disponibilité ou leurs propres pratiques de confidentialité.',
        ],
        [
            'title' => '9. Données personnelles et cookies',
            'body' => 'Les traitements de données personnelles liés au site sont décrits dans la page Vie privée et RGPD. Les demandes d\'accès, rectification, effacement, limitation, opposition ou portabilité peuvent être adressées à {contact_email} ou introduites via l\'espace membre lorsque la fonctionnalité est disponible.',
        ],
        [
            'title' => '10. Signalement',
            'body' => 'Pour signaler une erreur, un contenu problématique, une atteinte à un droit, une donnée personnelle publiée par erreur ou une faille de sécurité, contactez le club à {contact_email} avec les informations nécessaires à l\'identification de la page concernée.',
        ],
        [
            'title' => '11. Droit applicable',
            'body' => 'Les présentes mentions légales sont soumises au droit belge. En cas de litige, une résolution amiable avec le comité du club est privilégiée. À défaut, les juridictions belges compétentes pourront être saisies selon les règles applicables.',
        ],
        [
            'title' => '12. Mise à jour',
            'body' => 'Ces mentions peuvent être adaptées pour tenir compte de l\'évolution du site, du club, de l\'hébergement, des services proposés ou du cadre légal. La date affichée en haut de page indique la dernière version publiée.',
        ],
    ],
]);

$messages['en'] = array_replace($messages['en'] ?? [], [
    'title' => 'Legal notice',
    'summary' => 'This page identifies the ON4CRD website publisher, contact information, intellectual property rules, liability limits and GDPR references.',
    'updated_at_label' => 'Last updated',
    'updated_at' => '5 June 2026',
    'identity_title' => 'Publisher and hosting',
    'identity_editor' => 'Website publisher',
    'identity_address' => 'Address',
    'identity_contact' => 'Contact',
    'identity_publication_manager' => 'Publishing manager',
    'identity_hosting' => 'Hosting',
    'related_pages_title' => 'Related pages',
    'terms_link_label' => 'Terms of use',
    'privacy_link_label' => 'Privacy and GDPR',
    'sections' => [
        [
            'title' => '1. Website publisher',
            'body' => 'This website is published by {club_name}, an amateur radio club established at the following address: {postal_address}. The club public contact is {contact_email}.',
        ],
        [
            'title' => '2. Publishing manager',
            'body' => 'The publishing manager is {publication_manager}. Requests about published content, corrections, copyright or reports may be sent to {contact_email}.',
        ],
        [
            'title' => '3. Hosting',
            'body' => 'The website is hosted via {hosting_name}. Hosting address or reference: {hosting_address}. Hosting website or technical reference: {hosting_url}. If the actual hosting provider differs according to the deployment environment, this information must be adjusted in the website configuration.',
        ],
        [
            'title' => '4. Nature of the website',
            'body' => 'ON4CRD is an amateur radio association portal. It publishes club information, news, events, articles, wiki resources, albums, technical tools and member-related services. Information is provided for documentation, community and organizational purposes.',
        ],
        [
            'title' => '5. Intellectual property',
            'body' => 'Unless otherwise stated, all content on this website, including texts, articles, photographs, documents, logos, graphic elements, knowledge bases, structure and developments, belongs to {club_name}, its member contributors or their respective owners.',
            'items' => [
                'Any substantial reproduction, representation, modification, distribution or exploitation without prior authorization is prohibited, except where legally permitted.',
                'Third-party trademarks, logos, callsigns, documents or content remain the property of their owners.',
                'Short quotations and links to public pages are allowed when the ON4CRD source is clearly mentioned.',
            ],
        ],
        [
            'title' => '6. Editorial responsibility',
            'body' => 'The club takes reasonable care over the accuracy of published information. Errors, omissions, schedule changes, broken links or outdated information may nevertheless remain. ON4CRD cannot be held liable for inaccurate interpretation or inappropriate use of the information published.',
        ],
        [
            'title' => '7. Amateur radio information',
            'body' => 'Technical information, band plans, calculation tools, articles, experience reports and wiki content do not replace official texts, BIPT requirements, applicable IARU rules, manufacturer notices or each amateur radio operator\'s own obligations.',
        ],
        [
            'title' => '8. Hyperlinks',
            'body' => 'The website may contain links to third-party websites, maps, administrative documents, amateur radio tools, social networks or partners. ON4CRD does not exercise permanent control over these external resources and cannot guarantee their content, availability or privacy practices.',
        ],
        [
            'title' => '9. Personal data and cookies',
            'body' => 'Personal data processing connected with the website is described on the Privacy and GDPR page. Access, rectification, erasure, restriction, objection or portability requests may be sent to {contact_email} or submitted through the member area when the feature is available.',
        ],
        [
            'title' => '10. Reporting',
            'body' => 'To report an error, problematic content, rights infringement, personal data published by mistake or a security issue, contact the club at {contact_email} with the information needed to identify the relevant page.',
        ],
        [
            'title' => '11. Applicable law',
            'body' => 'This legal notice is governed by Belgian law. In case of dispute, an amicable resolution with the club committee is preferred. Failing that, the competent Belgian courts may be seized according to the applicable rules.',
        ],
        [
            'title' => '12. Updates',
            'body' => 'This notice may be adapted to reflect changes to the website, the club, hosting, offered services or the legal framework. The date shown at the top of the page indicates the latest published version.',
        ],
    ],
]);

return $messages;
