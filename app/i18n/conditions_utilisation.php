<?php
declare(strict_types=1);

$messages = [];
$messages['fr'] = require __DIR__ . '/conditions_utilisation/fr.php';
$messages['en'] = require __DIR__ . '/conditions_utilisation/en.php';
$messages['de'] = require __DIR__ . '/conditions_utilisation/de.php';
$messages['nl'] = require __DIR__ . '/conditions_utilisation/nl.php';
$messages['it'] = require __DIR__ . '/conditions_utilisation/it.php';
$messages['es'] = require __DIR__ . '/conditions_utilisation/es.php';
$messages['pt'] = require __DIR__ . '/conditions_utilisation/pt.php';
$messages['bg'] = require __DIR__ . '/conditions_utilisation/bg.php';
$messages['hr'] = require __DIR__ . '/conditions_utilisation/hr.php';
$messages['cs'] = require __DIR__ . '/conditions_utilisation/cs.php';
$messages['da'] = require __DIR__ . '/conditions_utilisation/da.php';
$messages['et'] = require __DIR__ . '/conditions_utilisation/et.php';
$messages['fi'] = require __DIR__ . '/conditions_utilisation/fi.php';
$messages['el'] = require __DIR__ . '/conditions_utilisation/el.php';
$messages['hu'] = require __DIR__ . '/conditions_utilisation/hu.php';
$messages['ga'] = require __DIR__ . '/conditions_utilisation/ga.php';
$messages['lv'] = require __DIR__ . '/conditions_utilisation/lv.php';
$messages['lt'] = require __DIR__ . '/conditions_utilisation/lt.php';
$messages['mt'] = require __DIR__ . '/conditions_utilisation/mt.php';
$messages['pl'] = require __DIR__ . '/conditions_utilisation/pl.php';
$messages['ro'] = require __DIR__ . '/conditions_utilisation/ro.php';
$messages['sk'] = require __DIR__ . '/conditions_utilisation/sk.php';
$messages['sl'] = require __DIR__ . '/conditions_utilisation/sl.php';
$messages['sv'] = require __DIR__ . '/conditions_utilisation/sv.php';
$messages['ar'] = require __DIR__ . '/conditions_utilisation/ar.php';
$messages['hi'] = require __DIR__ . '/conditions_utilisation/hi.php';
$messages['ja'] = require __DIR__ . '/conditions_utilisation/ja.php';
$messages['zh'] = require __DIR__ . '/conditions_utilisation/zh.php';
$messages['bn'] = require __DIR__ . '/conditions_utilisation/bn.php';
$messages['ru'] = require __DIR__ . '/conditions_utilisation/ru.php';
$messages['id'] = require __DIR__ . '/conditions_utilisation/id.php';

$messages['fr'] = array_replace($messages['fr'] ?? [], [
    'title' => 'Conditions générales d\'utilisation',
    'summary' => 'Les présentes conditions encadrent l\'accès au site {club_name}, à ses contenus publics et aux services proposés aux membres, contributeurs et visiteurs.',
    'updated_at_label' => 'Dernière mise à jour',
    'updated_at' => '05 juin 2026',
    'service_reference' => 'Service édité par',
    'identity_title' => 'Référence du service',
    'identity_editor' => 'Éditeur',
    'identity_contact' => 'Contact',
    'identity_address' => 'Adresse',
    'related_pages_title' => 'Pages associées',
    'legal_link_label' => 'Mentions légales',
    'privacy_link_label' => 'Vie privée et RGPD',
    'sections' => [
        [
            'title' => '1. Objet',
            'body' => 'Le site ON4CRD met à disposition des informations liées au Radio Club Durnal, à la pratique radioamateur, aux activités du club, aux articles techniques, au wiki, aux albums, aux petites annonces, aux outils radio et aux services réservés aux membres.',
        ],
        [
            'title' => '2. Acceptation des conditions',
            'body' => 'La consultation du site vaut acceptation des présentes conditions. L\'utilisateur qui crée un compte, propose un contenu ou utilise un service membre confirme agir de bonne foi et respecter le cadre légal belge, les règles radioamateurs applicables et les consignes du club.',
        ],
        [
            'title' => '3. Accès au site et aux services',
            'body' => 'Les pages publiques sont accessibles sans compte. Certains modules peuvent être réservés aux membres, aux administrateurs ou aux personnes disposant d\'une permission spécifique. ON4CRD peut suspendre temporairement l\'accès au site pour maintenance, sécurité, correction d\'incident ou évolution technique.',
        ],
        [
            'title' => '4. Comptes membres',
            'body' => 'Le compte membre permet d\'accéder aux fonctionnalités internes du club, notamment le profil, l\'annuaire selon les réglages de visibilité, les outils QSL, les notifications, les propositions de contenu et certains espaces documentaires.',
            'items' => [
                'Les informations transmises doivent être exactes, à jour et liées à l\'activité radioamateur ou à la gestion du club.',
                'Chaque utilisateur est responsable de la confidentialité de ses identifiants.',
                'Un compte peut être limité, suspendu ou supprimé en cas d\'usage abusif, de contenu illicite, d\'atteinte à la sécurité ou de non-respect des règles du club.',
            ],
        ],
        [
            'title' => '5. Contributions et contenus publiés',
            'body' => 'Les articles, propositions wiki, photos, documents, petites annonces, commentaires éditoriaux ou autres contenus transmis au site doivent être licites, exacts au meilleur de la connaissance de leur auteur et compatibles avec l\'objet du Radio Club Durnal.',
            'items' => [
                'L\'auteur conserve ses droits sur son contenu, mais autorise ON4CRD à l\'héberger, le modérer, le traduire, l\'adapter techniquement et le publier dans le cadre du site.',
                'Toute publication de photo, donnée personnelle, indicatif, document ou contenu appartenant à un tiers suppose l\'autorisation nécessaire.',
                'Les contenus proposés peuvent être refusés, corrigés, dépubliés ou supprimés par l\'équipe de modération.',
            ],
        ],
        [
            'title' => '6. Petites annonces et échanges entre utilisateurs',
            'body' => 'Les petites annonces sont un service de mise en relation autour du matériel radioamateur. ON4CRD n\'est pas partie aux échanges, ventes, dons ou transactions conclus entre utilisateurs. Chaque annonceur reste responsable de la description, de la disponibilité, du prix éventuel, du respect de la réglementation et du suivi de ses contacts.',
        ],
        [
            'title' => '7. Règles de conduite',
            'body' => 'L\'utilisateur s\'engage à ne pas publier ni transmettre de contenu illicite, diffamatoire, discriminatoire, violent, frauduleux, publicitaire non autorisé, portant atteinte à la vie privée, à la propriété intellectuelle ou à la sécurité du site.',
            'items' => [
                'Les informations techniques publiées sur les bandes, modes, antennes, logiciels ou équipements doivent être utilisées avec prudence et dans le respect des autorisations radioamateurs applicables.',
                'Les outils du site ne remplacent pas les textes officiels, les prescriptions du BIPT, les plans de bandes applicables ni les obligations propres à chaque opérateur.',
                'Toute tentative d\'intrusion, de scraping abusif, de contournement des permissions ou d\'altération du service est interdite.',
            ],
        ],
        [
            'title' => '8. Propriété intellectuelle',
            'body' => 'Sauf mention contraire, les textes, pages, visuels, logos, éléments graphiques, développements et documents publiés sur le site sont protégés. Leur reproduction ou réutilisation substantielle nécessite l\'accord préalable de {club_name} ou de l\'auteur concerné, hors exceptions prévues par la loi.',
        ],
        [
            'title' => '9. Données personnelles et cookies',
            'body' => 'Les données personnelles sont traitées pour la gestion du site, des membres, des contenus, de la sécurité, des communications et des demandes liées au club. Les détails, droits des personnes, durées de conservation et modalités de contact sont décrits dans la page Vie privée et RGPD.',
        ],
        [
            'title' => '10. Responsabilité',
            'body' => 'ON4CRD apporte un soin raisonnable aux informations publiées, mais ne garantit pas l\'absence totale d\'erreur, d\'omission, d\'indisponibilité ou de contenu obsolète. L\'utilisateur reste responsable de l\'usage des informations radioamateurs, techniques ou pratiques consultées sur le site.',
        ],
        [
            'title' => '11. Liens externes et services tiers',
            'body' => 'Le site peut pointer vers des ressources externes, cartes, documents, services radioamateurs, sources réglementaires ou sites partenaires. Ces ressources sont fournies pour information; ON4CRD ne contrôle pas leur disponibilité, leur exactitude continue ni leurs propres conditions d\'utilisation.',
        ],
        [
            'title' => '12. Modification des conditions',
            'body' => 'ON4CRD peut mettre à jour les présentes conditions pour tenir compte de l\'évolution du site, des modules proposés, du fonctionnement du club ou du cadre légal. La date de mise à jour affichée en haut de page fait foi.',
        ],
        [
            'title' => '13. Droit applicable',
            'body' => 'Les présentes conditions sont régies par le droit belge. En cas de difficulté, une résolution amiable avec le comité du club est privilégiée avant toute autre démarche.',
        ],
    ],
]);

$messages['en'] = array_replace($messages['en'] ?? [], [
    'title' => 'Terms of use',
    'summary' => 'These terms govern access to the {club_name} website, its public content and the services offered to members, contributors and visitors.',
    'updated_at_label' => 'Last updated',
    'updated_at' => '5 June 2026',
    'service_reference' => 'Service operated by',
    'identity_title' => 'Service reference',
    'identity_editor' => 'Publisher',
    'identity_contact' => 'Contact',
    'identity_address' => 'Address',
    'related_pages_title' => 'Related pages',
    'legal_link_label' => 'Legal notice',
    'privacy_link_label' => 'Privacy and GDPR',
    'sections' => [
        [
            'title' => '1. Purpose',
            'body' => 'The ON4CRD website provides information about Radio Club Durnal, amateur radio practice, club activities, technical articles, the wiki, albums, classifieds, radio tools and member-only services.',
        ],
        [
            'title' => '2. Acceptance of the terms',
            'body' => 'Browsing the website implies acceptance of these terms. Any user who creates an account, proposes content or uses a member service confirms that they act in good faith and comply with Belgian law, applicable amateur radio rules and club instructions.',
        ],
        [
            'title' => '3. Access to the website and services',
            'body' => 'Public pages are accessible without an account. Some modules may be limited to members, administrators or users holding a specific permission. ON4CRD may temporarily suspend access for maintenance, security, incident correction or technical changes.',
        ],
        [
            'title' => '4. Member accounts',
            'body' => 'The member account gives access to internal club features, including the profile, the directory according to visibility settings, QSL tools, notifications, content proposals and selected document areas.',
            'items' => [
                'Submitted information must be accurate, up to date and connected with amateur radio activity or club management.',
                'Each user is responsible for keeping their credentials confidential.',
                'An account may be limited, suspended or deleted in case of abusive use, unlawful content, security issues or breach of club rules.',
            ],
        ],
        [
            'title' => '5. Contributions and published content',
            'body' => 'Articles, wiki proposals, photos, documents, classifieds, editorial comments and any other content sent to the website must be lawful, accurate to the best of the author\'s knowledge and compatible with the purpose of Radio Club Durnal.',
            'items' => [
                'The author keeps their rights but authorizes ON4CRD to host, moderate, translate, technically adapt and publish the content within the website.',
                'Publishing photos, personal data, callsigns, documents or third-party content requires the necessary authorization.',
                'Proposed content may be refused, corrected, unpublished or deleted by the moderation team.',
            ],
        ],
        [
            'title' => '6. Classifieds and user exchanges',
            'body' => 'Classifieds are a connection service around amateur radio equipment. ON4CRD is not a party to exchanges, sales, donations or transactions concluded between users. Each advertiser remains responsible for the description, availability, possible price, legal compliance and follow-up of their contacts.',
        ],
        [
            'title' => '7. Conduct rules',
            'body' => 'Users must not publish or transmit unlawful, defamatory, discriminatory, violent, fraudulent, unauthorized advertising or privacy-infringing content, nor content that infringes intellectual property or website security.',
            'items' => [
                'Technical information about bands, modes, antennas, software or equipment must be used carefully and in compliance with applicable amateur radio authorizations.',
                'The website tools do not replace official texts, BIPT requirements, applicable band plans or each operator\'s own obligations.',
                'Any intrusion attempt, abusive scraping, permission bypass or alteration of the service is prohibited.',
            ],
        ],
        [
            'title' => '8. Intellectual property',
            'body' => 'Unless otherwise stated, texts, pages, visuals, logos, graphic elements, developments and documents published on the website are protected. Any substantial reproduction or reuse requires prior approval from {club_name} or the relevant author, except where legally permitted.',
        ],
        [
            'title' => '9. Personal data and cookies',
            'body' => 'Personal data is processed for website, member, content, security, communication and club-related request management. Details, data subject rights, retention periods and contact methods are described on the Privacy and GDPR page.',
        ],
        [
            'title' => '10. Liability',
            'body' => 'ON4CRD takes reasonable care over published information but does not guarantee the complete absence of errors, omissions, unavailability or outdated content. Users remain responsible for how they use amateur radio, technical or practical information found on the website.',
        ],
        [
            'title' => '11. External links and third-party services',
            'body' => 'The website may link to external resources, maps, documents, amateur radio services, regulatory sources or partner websites. These resources are provided for information; ON4CRD does not control their availability, continued accuracy or own terms of use.',
        ],
        [
            'title' => '12. Changes to the terms',
            'body' => 'ON4CRD may update these terms to reflect changes to the website, offered modules, club operations or the legal framework. The update date displayed at the top of the page is the reference.',
        ],
        [
            'title' => '13. Applicable law',
            'body' => 'These terms are governed by Belgian law. In case of difficulty, an amicable resolution with the club committee is preferred before any other step.',
        ],
    ],
]);

return $messages;
