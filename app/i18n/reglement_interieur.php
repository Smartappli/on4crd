<?php
declare(strict_types=1);

$messages = [];
$messages['fr'] = require_once __DIR__ . '/reglement_interieur/fr.php';
$messages['en'] = require_once __DIR__ . '/reglement_interieur/en.php';
$messages['de'] = require_once __DIR__ . '/reglement_interieur/de.php';
$messages['nl'] = require_once __DIR__ . '/reglement_interieur/nl.php';
$messages['it'] = require_once __DIR__ . '/reglement_interieur/it.php';
$messages['es'] = require_once __DIR__ . '/reglement_interieur/es.php';
$messages['pt'] = require_once __DIR__ . '/reglement_interieur/pt.php';
$messages['bg'] = require_once __DIR__ . '/reglement_interieur/bg.php';
$messages['hr'] = require_once __DIR__ . '/reglement_interieur/hr.php';
$messages['cs'] = require_once __DIR__ . '/reglement_interieur/cs.php';
$messages['da'] = require_once __DIR__ . '/reglement_interieur/da.php';
$messages['et'] = require_once __DIR__ . '/reglement_interieur/et.php';
$messages['fi'] = require_once __DIR__ . '/reglement_interieur/fi.php';
$messages['el'] = require_once __DIR__ . '/reglement_interieur/el.php';
$messages['hu'] = require_once __DIR__ . '/reglement_interieur/hu.php';
$messages['ga'] = require_once __DIR__ . '/reglement_interieur/ga.php';
$messages['lv'] = require_once __DIR__ . '/reglement_interieur/lv.php';
$messages['lt'] = require_once __DIR__ . '/reglement_interieur/lt.php';
$messages['mt'] = require_once __DIR__ . '/reglement_interieur/mt.php';
$messages['pl'] = require_once __DIR__ . '/reglement_interieur/pl.php';
$messages['ro'] = require_once __DIR__ . '/reglement_interieur/ro.php';
$messages['sk'] = require_once __DIR__ . '/reglement_interieur/sk.php';
$messages['sl'] = require_once __DIR__ . '/reglement_interieur/sl.php';
$messages['sv'] = require_once __DIR__ . '/reglement_interieur/sv.php';
$messages['ar'] = require_once __DIR__ . '/reglement_interieur/ar.php';
$messages['hi'] = require_once __DIR__ . '/reglement_interieur/hi.php';
$messages['ja'] = require_once __DIR__ . '/reglement_interieur/ja.php';
$messages['zh'] = require_once __DIR__ . '/reglement_interieur/zh.php';
$messages['bn'] = require_once __DIR__ . '/reglement_interieur/bn.php';
$messages['ru'] = require_once __DIR__ . '/reglement_interieur/ru.php';
$messages['id'] = require_once __DIR__ . '/reglement_interieur/id.php';

$messages['fr'] = array_replace($messages['fr'] ?? [], [
    'summary' => 'Projet de règlement d\'ordre intérieur du {club_name}. Ce document complète les statuts, précise le fonctionnement quotidien du club et fixe les règles applicables aux membres, invités, activités, équipements radio et services numériques.',
    'updated_at_label' => 'Dernière mise à jour',
    'updated_at' => '05 juin 2026',
    'status_label' => 'Statut',
    'status' => 'Projet à valider par le comité et, si les statuts l\'exigent, par l\'assemblée compétente',
    'contact_label' => 'Contact club',
    'related_pages_title' => 'Pages associées',
    'membership_link_label' => 'Devenir membre',
    'terms_link_label' => 'Conditions générales d\'utilisation',
    'privacy_link_label' => 'Vie privée et RGPD',
    'sections' => [
        [
            'title' => 'Article 1. Objet et portée',
            'body' => 'Le présent règlement d\'ordre intérieur organise la vie pratique du {club_name}: accueil des membres, participation aux activités, utilisation du matériel, règles de sécurité, conduite sur les espaces numériques et modalités de modération.',
            'items' => [
                'Il s\'applique aux membres, candidats membres, invités, bénévoles, visiteurs et contributeurs utilisant les services ou participant aux activités du club.',
                'Il ne remplace pas les statuts, les décisions valablement prises par les organes du club, le droit belge, les règles du BIPT ni les obligations propres à chaque radioamateur.',
                'En cas de contradiction, les statuts, la loi et les décisions formelles des organes compétents prévalent sur le présent règlement.',
            ],
        ],
        [
            'title' => 'Article 2. Esprit du club',
            'body' => 'ON4CRD est un club radioamateur fondé sur l\'entraide, l\'expérimentation technique, la formation, la convivialité et le respect des règles applicables au service radioamateur. Les échanges doivent rester courtois, inclusifs, non commerciaux dans l\'esprit du service amateur et compatibles avec l\'objet du club.',
        ],
        [
            'title' => 'Article 3. Admission et qualité de membre',
            'body' => 'Toute personne souhaitant rejoindre le club introduit une demande d\'adhésion selon les modalités fixées par le comité. L\'adhésion suppose l\'acceptation des statuts, du présent règlement, des conditions d\'utilisation du site et des règles de confidentialité applicables.',
            'items' => [
                'Le comité peut demander les informations nécessaires à l\'identification du membre, à la gestion de l\'adhésion et aux communications du club.',
                'Les mineurs peuvent participer aux activités selon les conditions fixées par le comité, avec autorisation parentale lorsque nécessaire et supervision adaptée.',
                'Un invité peut participer ponctuellement à une activité sous la responsabilité du membre ou du responsable qui l\'accueille.',
            ],
        ],
        [
            'title' => 'Article 4. Cotisation et accès aux services',
            'body' => 'Le montant, la période de validité et les modalités de paiement de la cotisation sont fixés par le comité ou l\'organe compétent selon les statuts. Le non-paiement après rappel peut entraîner la suspension de l\'accès aux services réservés aux membres.',
            'items' => [
                'Sauf décision contraire du comité, une cotisation payée reste acquise au club.',
                'Le comité peut prévoir des modalités particulières pour les jeunes, familles, membres de soutien, invités, intervenants ou situations exceptionnelles.',
            ],
        ],
        [
            'title' => 'Article 5. Droits des membres',
            'body' => 'Chaque membre en ordre de cotisation bénéficie des services ouverts à sa catégorie de membre et peut participer aux activités du club dans le respect des disponibilités, des capacités d\'accueil et des règles de sécurité.',
            'items' => [
                'Il peut proposer une activité, un article, une ressource wiki, une annonce ou une amélioration du site.',
                'Il peut demander l\'accès, la rectification ou la suppression des données personnelles le concernant selon la procédure RGPD du club.',
                'Il peut signaler au comité toute difficulté, anomalie, incident de sécurité ou comportement contraire au présent règlement.',
            ],
        ],
        [
            'title' => 'Article 6. Obligations des membres',
            'body' => 'Chaque membre s\'engage à adopter un comportement loyal, respectueux et responsable envers le club, ses membres, ses invités, ses partenaires et le public.',
            'items' => [
                'Maintenir à jour ses coordonnées utiles et ne pas usurper l\'identité, l\'indicatif ou les accès d\'un tiers.',
                'Respecter la réglementation radioamateur, les règles de sécurité, les consignes des responsables d\'activité et les décisions du comité.',
                'Ne pas diffuser de propos discriminatoires, injurieux, diffamatoires, menaçants ou contraires à l\'esprit associatif.',
                'Ne pas utiliser les activités, outils, listes de membres ou canaux du club à des fins de prospection commerciale non autorisée.',
            ],
        ],
        [
            'title' => 'Article 7. Comité, responsables et bénévoles',
            'body' => 'Le comité assure l\'organisation quotidienne du club, la coordination des activités, la gestion des ressources, la modération des contenus et la sécurité des services. Il peut désigner des responsables d\'activité, modérateurs, gestionnaires de matériel ou référents techniques.',
            'items' => [
                'Les responsabilités confiées doivent être exercées avec prudence, transparence et dans l\'intérêt du club.',
                'Tout conflit d\'intérêts significatif doit être signalé au comité avant décision ou engagement financier.',
                'Les dépenses engagées au nom du club nécessitent un accord préalable et des justificatifs exploitables.',
            ],
        ],
        [
            'title' => 'Article 8. Réunions et activités',
            'body' => 'Les réunions, formations, démonstrations, sorties, contests, ateliers, activations et événements sont annoncés par les canaux habituels du club. Les inscriptions peuvent être limitées pour des raisons de capacité, de sécurité, d\'assurance, de matériel ou d\'encadrement.',
            'items' => [
                'Les participants respectent les horaires, lieux, consignes, procédures d\'annulation et règles propres à chaque activité.',
                'Le responsable d\'activité peut refuser ou interrompre une participation en cas de risque de sécurité, comportement inadéquat ou non-respect des consignes.',
                'Les photos ou comptes rendus d\'activité peuvent être publiés par le club, sous réserve du respect du droit à l\'image et des demandes raisonnables de retrait.',
            ],
        ],
        [
            'title' => 'Article 9. Utilisation des stations, indicatifs et équipements radio',
            'body' => 'Toute émission radio réalisée dans le cadre du club doit respecter la réglementation belge, les conditions de licence, les plans de bandes applicables, les limites de puissance, les règles de trafic et les consignes du responsable de station.',
            'items' => [
                'Seules les personnes dûment autorisées peuvent émettre. Les personnes en formation ou non titulaires d\'une autorisation suffisante doivent être encadrées selon les règles applicables.',
                'L\'usage de l\'indicatif du club ou d\'une station de club nécessite l\'accord du responsable désigné et, le cas échéant, l\'inscription correcte des contacts dans le journal prévu.',
                'Aucun réglage risqué, modification matérielle, branchement d\'antenne, manipulation haute tension ou intervention sur installation ne peut être effectué sans accord du responsable technique.',
                'Le matériel emprunté doit être rendu dans l\'état convenu. Toute panne, casse, perte ou anomalie doit être signalée immédiatement.',
            ],
        ],
        [
            'title' => 'Article 10. Sécurité des personnes et des installations',
            'body' => 'La sécurité prime sur toute activité radio, technique ou événementielle. Chaque participant doit appliquer les consignes de prudence liées à l\'électricité, aux antennes, aux travaux en hauteur, à la météo, aux câbles, aux batteries, aux outils, aux déplacements et aux radiofréquences.',
            'items' => [
                'Les travaux présentant un risque particulier ne se font pas seul et doivent être préparés avec le responsable compétent.',
                'Les installations temporaires doivent être démontées ou sécurisées selon les consignes de fin d\'activité.',
                'Tout incident, quasi-incident, blessure, dommage matériel ou situation dangereuse doit être communiqué rapidement au responsable présent ou au comité.',
            ],
        ],
        [
            'title' => 'Article 11. Site web, comptes et services numériques',
            'body' => 'Les comptes, modules membres, annuaire, wiki, articles, albums, petites annonces, newsletters, notifications et outils numériques ON4CRD sont fournis pour la gestion du club et la communauté radioamateur.',
            'items' => [
                'Les identifiants sont personnels. Le partage de compte, la tentative d\'intrusion, le contournement de permissions ou l\'extraction abusive de données sont interdits.',
                'Les contenus proposés peuvent être modérés, corrigés, traduits automatiquement, dépubliés ou supprimés lorsque nécessaire.',
                'Les informations de l\'annuaire et des profils ne peuvent pas être copiées ou utilisées hors du cadre du club sans base légitime et respect du RGPD.',
            ],
        ],
        [
            'title' => 'Article 12. Contributions, publications et droit à l\'image',
            'body' => 'Les membres peuvent contribuer aux articles, au wiki, aux albums, à la bibliothèque, aux actualités et aux retours d\'expérience. Chaque contributeur garantit disposer des droits nécessaires sur les textes, images, documents ou médias qu\'il transmet.',
            'items' => [
                'Les contenus doivent être exacts au meilleur de la connaissance du contributeur, licites et compatibles avec l\'objet du club.',
                'Les photos identifiant clairement une personne, en particulier un mineur, doivent être publiées avec prudence et retirées en cas de demande légitime.',
                'Le club peut adapter la mise en forme, corriger les fautes, ajouter une traduction ou modifier un titre pour préserver la cohérence éditoriale du site.',
            ],
        ],
        [
            'title' => 'Article 13. Petites annonces et échanges de matériel',
            'body' => 'Les petites annonces sont un service de mise en relation. ON4CRD n\'est pas vendeur, acheteur, intermédiaire commercial ni garant des transactions conclues entre utilisateurs.',
            'items' => [
                'L\'annonceur reste responsable de l\'exactitude de son annonce, de la disponibilité du matériel et du respect de la réglementation applicable.',
                'Les annonces manifestement frauduleuses, illicites, dangereuses, hors sujet ou contraires à l\'esprit du club peuvent être refusées ou retirées.',
            ],
        ],
        [
            'title' => 'Article 14. Bibliothèque, documents et ressources',
            'body' => 'Les documents partagés par le club, les membres ou les partenaires doivent être utilisés dans le respect de leur licence, de leur finalité et des droits de leurs auteurs. Les ressources réservées aux membres ne peuvent pas être redistribuées publiquement sans autorisation.',
        ],
        [
            'title' => 'Article 15. Usage du nom, du logo et des canaux du club',
            'body' => 'L\'utilisation du nom ON4CRD, du logo, de l\'indicatif du club, des canaux de communication ou de la qualité de représentant du club nécessite l\'autorisation du comité, sauf usage personnel raisonnable indiquant simplement l\'appartenance au club.',
        ],
        [
            'title' => 'Article 16. Confidentialité et données personnelles',
            'body' => 'Les données personnelles traitées par le club sont limitées aux finalités décrites dans la notice Vie privée et RGPD. Les membres doivent respecter la confidentialité des données auxquelles ils accèdent dans le cadre du club.',
            'items' => [
                'Les listes de membres, coordonnées, photos, données de profil, demandes RGPD et informations internes ne peuvent pas être communiquées à des tiers sans base légitime.',
                'Toute demande relative aux données personnelles peut être adressée à {contact_email} ou introduite via l\'espace membre lorsque la fonctionnalité est disponible.',
            ],
        ],
        [
            'title' => 'Article 17. Manquements et mesures disciplinaires',
            'body' => 'En cas de non-respect du présent règlement, des statuts, des règles de sécurité ou du cadre légal, le comité privilégie une réponse proportionnée et graduée.',
            'items' => [
                'Les mesures peuvent inclure un rappel, une demande de correction, un avertissement, une restriction temporaire d\'accès, une suspension d\'activité ou une proposition d\'exclusion selon les statuts.',
                'Sauf urgence liée à la sécurité ou à la protection du club, la personne concernée doit pouvoir être informée des faits reprochés et présenter ses observations.',
                'Une mesure conservatoire immédiate peut être prise pour protéger les personnes, les équipements, les données, l\'indicatif du club ou la sécurité du site.',
            ],
        ],
        [
            'title' => 'Article 18. Modification du règlement',
            'body' => 'Le présent règlement peut être modifié sur proposition du comité ou de l\'organe compétent selon les statuts. La version applicable est celle publiée sur le site ou communiquée aux membres après validation.',
        ],
        [
            'title' => 'Article 19. Entrée en vigueur',
            'body' => 'Le règlement entre en vigueur à la date fixée par l\'organe qui l\'approuve. La version publiée sur cette page constitue un projet tant que le comité ou l\'assemblée compétente ne l\'a pas formellement validée.',
        ],
    ],
]);

$messages['en'] = array_replace($messages['en'] ?? [], [
    'summary' => 'Draft internal rules for {club_name}. This document complements the statutes, clarifies day-to-day club operations and sets rules for members, guests, activities, radio equipment and digital services.',
    'updated_at_label' => 'Last updated',
    'updated_at' => '5 June 2026',
    'status_label' => 'Status',
    'status' => 'Draft to be validated by the committee and, if required by the statutes, by the competent assembly',
    'contact_label' => 'Club contact',
    'related_pages_title' => 'Related pages',
    'membership_link_label' => 'Become a member',
    'terms_link_label' => 'Terms of use',
    'privacy_link_label' => 'Privacy and GDPR',
    'sections' => [
        [
            'title' => 'Article 1. Purpose and scope',
            'body' => 'These internal rules organize the practical life of {club_name}: member welcome, participation in activities, equipment use, safety rules, conduct on digital spaces and moderation procedures.',
            'items' => [
                'They apply to members, candidate members, guests, volunteers, visitors and contributors using club services or taking part in club activities.',
                'They do not replace the statutes, valid decisions taken by club bodies, Belgian law, BIPT rules or each amateur radio operator\'s own obligations.',
                'In case of contradiction, the statutes, the law and formal decisions of the competent bodies prevail over these rules.',
            ],
        ],
        [
            'title' => 'Article 2. Club spirit',
            'body' => 'ON4CRD is an amateur radio club based on mutual help, technical experimentation, training, conviviality and respect for the rules applicable to the amateur radio service. Exchanges must remain courteous, inclusive, non-commercial in the spirit of the amateur service and compatible with the club purpose.',
        ],
        [
            'title' => 'Article 3. Admission and membership',
            'body' => 'Anyone wishing to join the club submits a membership request according to the procedure set by the committee. Membership implies acceptance of the statutes, these rules, the website terms of use and the applicable privacy rules.',
            'items' => [
                'The committee may request the information needed to identify the member, manage membership and communicate with the club.',
                'Minors may take part in activities under the conditions set by the committee, with parental authorization where necessary and appropriate supervision.',
                'A guest may occasionally take part in an activity under the responsibility of the member or organizer welcoming them.',
            ],
        ],
        [
            'title' => 'Article 4. Fees and access to services',
            'body' => 'The amount, validity period and payment methods for the membership fee are set by the committee or competent body according to the statutes. Non-payment after reminder may lead to suspension of access to member-only services.',
            'items' => [
                'Unless decided otherwise by the committee, paid fees remain acquired by the club.',
                'The committee may define specific arrangements for young people, families, supporting members, guests, speakers or exceptional situations.',
            ],
        ],
        [
            'title' => 'Article 5. Member rights',
            'body' => 'Each member in good standing benefits from the services open to their membership category and may take part in club activities subject to availability, reception capacity and safety rules.',
            'items' => [
                'They may propose an activity, article, wiki resource, classified ad or website improvement.',
                'They may request access, correction or deletion of personal data concerning them under the club GDPR procedure.',
                'They may report any difficulty, anomaly, safety incident or conduct contrary to these rules to the committee.',
            ],
        ],
        [
            'title' => 'Article 6. Member obligations',
            'body' => 'Each member undertakes to behave loyally, respectfully and responsibly towards the club, its members, guests, partners and the public.',
            'items' => [
                'Keep useful contact details up to date and do not impersonate another person, callsign or access credential.',
                'Comply with amateur radio regulations, safety rules, activity organizer instructions and committee decisions.',
                'Do not disseminate discriminatory, insulting, defamatory, threatening or non-associative statements.',
                'Do not use club activities, tools, member lists or channels for unauthorized commercial prospecting.',
            ],
        ],
        [
            'title' => 'Article 7. Committee, organizers and volunteers',
            'body' => 'The committee handles day-to-day club organization, activity coordination, resource management, content moderation and service security. It may appoint activity organizers, moderators, equipment managers or technical contacts.',
            'items' => [
                'Entrusted responsibilities must be carried out carefully, transparently and in the interest of the club.',
                'Any significant conflict of interest must be reported to the committee before a decision or financial commitment.',
                'Expenses made on behalf of the club require prior approval and usable supporting documents.',
            ],
        ],
        [
            'title' => 'Article 8. Meetings and activities',
            'body' => 'Meetings, training sessions, demonstrations, outings, contests, workshops, activations and events are announced through the usual club channels. Registrations may be limited for capacity, safety, insurance, equipment or supervision reasons.',
            'items' => [
                'Participants comply with schedules, places, instructions, cancellation procedures and rules specific to each activity.',
                'The activity organizer may refuse or interrupt participation in case of safety risk, inappropriate conduct or failure to follow instructions.',
                'Photos or activity reports may be published by the club, subject to image rights and reasonable withdrawal requests.',
            ],
        ],
        [
            'title' => 'Article 9. Use of stations, callsigns and radio equipment',
            'body' => 'Any radio transmission performed within the club must comply with Belgian regulations, licence conditions, applicable band plans, power limits, traffic rules and instructions from the station manager.',
            'items' => [
                'Only duly authorized persons may transmit. Trainees or persons without sufficient authorization must be supervised according to applicable rules.',
                'Use of the club callsign or a club station requires approval from the appointed manager and, where applicable, correct logging of contacts.',
                'No risky adjustment, hardware modification, antenna connection, high-voltage handling or installation work may be carried out without approval from the technical manager.',
                'Borrowed equipment must be returned in the agreed condition. Any fault, breakage, loss or anomaly must be reported immediately.',
            ],
        ],
        [
            'title' => 'Article 10. Safety of people and installations',
            'body' => 'Safety takes precedence over any radio, technical or event activity. Each participant must apply precautions related to electricity, antennas, height work, weather, cables, batteries, tools, travel and radiofrequency exposure.',
            'items' => [
                'Work involving particular risk must not be done alone and must be prepared with the competent organizer.',
                'Temporary installations must be dismantled or secured according to end-of-activity instructions.',
                'Any incident, near miss, injury, equipment damage or dangerous situation must be quickly communicated to the organizer present or to the committee.',
            ],
        ],
        [
            'title' => 'Article 11. Website, accounts and digital services',
            'body' => 'ON4CRD accounts, member modules, directory, wiki, articles, albums, classifieds, newsletters, notifications and digital tools are provided for club management and the amateur radio community.',
            'items' => [
                'Credentials are personal. Account sharing, intrusion attempts, permission bypassing or abusive data extraction are prohibited.',
                'Proposed content may be moderated, corrected, automatically translated, unpublished or deleted where necessary.',
                'Directory and profile information may not be copied or used outside the club framework without legitimate basis and GDPR compliance.',
            ],
        ],
        [
            'title' => 'Article 12. Contributions, publications and image rights',
            'body' => 'Members may contribute to articles, the wiki, albums, the library, news and experience reports. Each contributor guarantees that they hold the necessary rights over the texts, images, documents or media they submit.',
            'items' => [
                'Content must be lawful, accurate to the best of the contributor\'s knowledge and compatible with the club purpose.',
                'Photos clearly identifying a person, especially a minor, must be published carefully and withdrawn in case of a legitimate request.',
                'The club may adapt formatting, correct mistakes, add a translation or change a title to preserve the website editorial consistency.',
            ],
        ],
        [
            'title' => 'Article 13. Classifieds and equipment exchanges',
            'body' => 'Classifieds are a connection service. ON4CRD is not seller, buyer, commercial intermediary or guarantor of transactions concluded between users.',
            'items' => [
                'The advertiser remains responsible for the accuracy of their ad, equipment availability and compliance with applicable rules.',
                'Ads that are clearly fraudulent, unlawful, dangerous, off-topic or contrary to the spirit of the club may be refused or withdrawn.',
            ],
        ],
        [
            'title' => 'Article 14. Library, documents and resources',
            'body' => 'Documents shared by the club, members or partners must be used in compliance with their licence, purpose and authors\' rights. Member-only resources may not be redistributed publicly without authorization.',
        ],
        [
            'title' => 'Article 15. Use of the club name, logo and channels',
            'body' => 'Use of the ON4CRD name, logo, club callsign, communication channels or representative capacity requires committee authorization, except for reasonable personal use simply indicating club membership.',
        ],
        [
            'title' => 'Article 16. Confidentiality and personal data',
            'body' => 'Personal data processed by the club is limited to the purposes described in the Privacy and GDPR notice. Members must respect the confidentiality of data they access within the club framework.',
            'items' => [
                'Member lists, contact details, photos, profile data, GDPR requests and internal information may not be disclosed to third parties without legitimate basis.',
                'Any personal data request may be sent to {contact_email} or submitted through the member area when the feature is available.',
            ],
        ],
        [
            'title' => 'Article 17. Breaches and disciplinary measures',
            'body' => 'In case of non-compliance with these rules, the statutes, safety rules or the legal framework, the committee favours a proportionate and gradual response.',
            'items' => [
                'Measures may include a reminder, a correction request, a warning, temporary access restriction, activity suspension or exclusion proposal according to the statutes.',
                'Except in urgent cases related to safety or club protection, the person concerned should be informed of the alleged facts and be able to provide observations.',
                'An immediate protective measure may be taken to protect people, equipment, data, the club callsign or website security.',
            ],
        ],
        [
            'title' => 'Article 18. Changes to the rules',
            'body' => 'These rules may be amended on proposal from the committee or competent body according to the statutes. The applicable version is the one published on the website or communicated to members after validation.',
        ],
        [
            'title' => 'Article 19. Entry into force',
            'body' => 'The rules enter into force on the date set by the approving body. The version published on this page remains a draft until formally validated by the committee or competent assembly.',
        ],
    ],
]);

return $messages;
