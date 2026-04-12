<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$message = '';
$error = '';
$installLockFile = __DIR__ . '/storage/install.lock';
$installAllowed = (bool) config('app.allow_install', false);
if (!$installAllowed || is_file($installLockFile)) {
    http_response_code(403);
    echo render_layout('<div class="card"><h1>Installation verrouillée</h1><p>Activez temporairement <code>app.allow_install</code> puis supprimez le verrou uniquement pour l\'installation initiale.</p></div>', 'Installation verrouillée');
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $schema = file_get_contents(__DIR__ . '/schema/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Impossible de lire le schéma SQL.');
        }
        db()->exec($schema);

        $permissions = [
            'admin.access' => 'Accès administration',
            'members.manage' => 'Gérer les membres',
            'news.submit' => 'Proposer des actualités',
            'news.moderate' => 'Modérer et publier les actualités',
            'articles.manage' => 'Gérer les articles techniques',
            'wiki.edit' => 'Contribuer au wiki',
            'wiki.moderate' => 'Valider le wiki',
            'albums.manage' => 'Gérer les albums',
            'albums.sync' => 'Synchroniser les albums publics',
            'dashboard.manage' => 'Gérer le tableau de bord',
            'qsl.manage' => 'Utiliser le module QSL',
            'chatbot.manage' => 'Voir les logs du chatbot',
            'ads.submit' => 'Gérer ses publicités',
            'ads.moderate' => 'Modérer les publicités',
            'ads.manage_all' => 'Gérer toutes les publicités et statistiques',
            'modules.manage' => 'Gérer les modules du site',
            'press.manage' => 'Gérer les contacts et communiqués de presse',
            'editorial.manage' => 'Gérer les contenus éditoriaux multilingues',
            'translations.review' => 'Relire et valider les traductions',
            'live_feeds.manage' => 'Gérer finement les flux live',
            'events.manage' => 'Gérer l’agenda et les événements',
            'shop.manage' => 'Gérer la boutique',
            'auctions.manage' => 'Gérer les enchères',
        ];
        $roles = [
            'super_admin' => array_keys($permissions),
            'member' => ['dashboard.manage', 'wiki.edit', 'qsl.manage'],
            'section_cm' => ['news.submit', 'dashboard.manage', 'wiki.edit', 'qsl.manage'],
            'editor' => ['admin.access', 'articles.manage', 'albums.manage', 'news.submit', 'press.manage', 'editorial.manage', 'translations.review'],
            'news_moderator' => ['admin.access', 'news.submit', 'news.moderate', 'translations.review'],
            'wiki_validator' => ['admin.access', 'wiki.edit', 'wiki.moderate'],
            'live_feed_manager' => ['admin.access', 'live_feeds.manage'],
            'events_manager' => ['admin.access', 'events.manage', 'translations.review'],
            'shop_manager' => ['admin.access', 'shop.manage'],
            'auction_manager' => ['admin.access', 'auctions.manage'],
            'advertiser' => ['ads.submit'],
            'ad_manager' => ['admin.access', 'ads.submit', 'ads.moderate', 'ads.manage_all'],
        ];

        $permStmt = db()->prepare('INSERT IGNORE INTO permissions (code, label) VALUES (?, ?)');
        foreach ($permissions as $code => $label) {
            $permStmt->execute([$code, $label]);
        }
        $roleStmt = db()->prepare('INSERT IGNORE INTO roles (code, label) VALUES (?, ?)');
        foreach ($roles as $code => $codes) {
            $roleStmt->execute([$code, ucwords(str_replace('_', ' ', $code))]);
        }
        $roleMap = [];
        foreach (db()->query('SELECT id, code FROM roles')->fetchAll() as $role) {
            $roleMap[$role['code']] = (int) $role['id'];
        }
        $permMap = [];
        foreach (db()->query('SELECT id, code FROM permissions')->fetchAll() as $perm) {
            $permMap[$perm['code']] = (int) $perm['id'];
        }
        $rolePermStmt = db()->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        foreach ($roles as $roleCode => $permCodes) {
            foreach ($permCodes as $permCode) {
                $rolePermStmt->execute([$roleMap[$roleCode], $permMap[$permCode]]);
            }
        }

        seed_modules();
        seed_dashboard_widgets();
        seed_ad_placements();
        seed_live_feeds();

        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? 'ON4CRD')));
        $fullName = trim((string) ($_POST['full_name'] ?? 'Administrateur'));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($password === '') {
            throw new RuntimeException('Mot de passe requis.');
        }
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        db()->prepare('INSERT INTO members (callsign, full_name, email, password_hash, qth, locator, bio, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
            ->execute([$callsign, $fullName, $email !== '' ? $email : null, $hash, 'Durnal', 'JO20', 'Administrateur principal du site']);
        $memberId = (int) db()->lastInsertId();
        db()->prepare('INSERT INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([$memberId, $roleMap['super_admin']]);

        $sectionStmt = db()->prepare('INSERT IGNORE INTO news_sections (slug, name, sort_order) VALUES (?, ?, ?)');
        $sectionStmt->execute(['club', 'Vie du club', 10]);
        $sectionStmt->execute(['technique', 'Technique', 20]);
        $sectionStmt->execute(['events', 'Événements', 30]);
        $clubSectionId = (int) db()->query("SELECT id FROM news_sections WHERE slug = 'club'")->fetchColumn();
        db()->prepare('INSERT INTO news_section_managers (member_id, section_id) VALUES (?, ?)')->execute([$memberId, $clubSectionId]);

        db()->prepare('INSERT INTO articles (slug, title, excerpt, content, status, author_id) VALUES (?, ?, ?, ?, ?, ?)')->execute([
            'reglages-station-club',
            'Réglages de base de la station club',
            'Rappel des réglages essentiels avant une activité.',
            '<p>Vérifiez alimentation, ROS, journal de trafic et sécurité avant chaque activité.</p>',
            'published',
            $memberId,
        ]);
        db()->prepare('INSERT INTO articles (slug, title, excerpt, content, status, author_id) VALUES (?, ?, ?, ?, ?, ?)')->execute([
            'propagation-debuter',
            'Comprendre la propagation pour débuter',
            'Quelques repères pour savoir quand une bande a des chances d’être ouverte.',
            '<p>La propagation dépend de l’activité solaire, de l’heure, de la saison et de la bande utilisée.</p>',
            'published',
            $memberId,
        ]);
        db()->prepare('INSERT INTO wiki_pages (slug, title, content, author_id) VALUES (?, ?, ?, ?)')->execute([
            'glossaire-radio',
            'Glossaire radio',
            '<p>Quelques termes utiles : ROS, QTH, QSL, DX, pile-up, split.</p>',
            $memberId,
        ]);

        $editorialDefaults = [
            'committee.title' => 'Le comité ON4CRD',
            'committee.intro' => 'Une équipe au service du club, de ses membres et de ses projets techniques.',
            'committee.mission' => 'Coordination du club, accueil, suivi des activités et relations extérieures.',
            'press.title' => 'Presse et informations médias',
            'press.intro' => 'Le club met à disposition des contacts, documents et ressources pour la presse locale et spécialisée.',
            'press.contact' => 'Pour une demande média, utilisez les contacts ci-dessous ou le dossier presse.',
        ];
        $editorialStmt = db()->prepare('INSERT INTO editorial_contents (content_key, fr_text) VALUES (?, ?) ON DUPLICATE KEY UPDATE fr_text = VALUES(fr_text)');
        foreach ($editorialDefaults as $key => $value) {
            $editorialStmt->execute([$key, $value]);
            auto_translate_editorial_key($key);
        }

        db()->prepare('INSERT INTO shop_categories (slug, name, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)')->execute(['club', 'Produits du club', 'Textiles, accessoires et petite promotion du radio-club.', 10, 1]);
        db()->prepare('INSERT INTO shop_categories (slug, name, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)')->execute(['atelier', 'Atelier & matériel', 'Petites fournitures et articles utiles pour les activités pratiques.', 20, 1]);
        $clubCategoryId = (int) db()->query("SELECT id FROM shop_categories WHERE slug = 'club'")->fetchColumn();
        $atelierCategoryId = (int) db()->query("SELECT id FROM shop_categories WHERE slug = 'atelier'")->fetchColumn();
        db()->prepare('INSERT INTO shop_products (category_id, slug, title, summary, description, price_cents, stock_qty, image_url, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$clubCategoryId, 't-shirt-on4crd', 'T-shirt ON4CRD', 'T-shirt du club pour les activités publiques et les démonstrations.', '<p>T-shirt simple et robuste aux couleurs du club.</p>', 1800, 25, '', 1, 'published']);
        db()->prepare('INSERT INTO shop_products (category_id, slug, title, summary, description, price_cents, stock_qty, image_url, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$atelierCategoryId, 'kit-initiation-morse', 'Kit initiation Morse', 'Petit kit pédagogique pour découvrir le Morse en atelier.', '<p>Kit d’initiation avec support de cours et matériel léger.</p>', 1200, 15, '', 1, 'published']);

        $auctionStart = date('Y-m-d H:i:s', strtotime('+1 day'));
        $auctionEnd = date('Y-m-d H:i:s', strtotime('+8 days'));
        db()->prepare('INSERT INTO auction_lots (slug, title, summary, description, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, current_price_cents, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(['lot-recepteur-vintage', 'Lot récepteur vintage', 'Récepteur vintage révisé, proposé au bénéfice du club.', '<p>Un lot pensé pour une première vente aux enchères du club.</p>', 4500, 7000, 500, 12000, 0, $auctionStart, $auctionEnd, 'scheduled']);

        db()->prepare('INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$clubSectionId, $memberId, 'bienvenue-sur-le-nouveau-site', 'Bienvenue sur le site ON4CRD', 'Le site démarre avec actualités, profils radioamateurs riches, wiki, QSL, widgets live et zone membre.', '<p>Le nouveau site ON4CRD met l’accent sur la vie du club.</p>', 'published', date('Y-m-d H:i:s')]);

        db()->prepare('INSERT INTO events (slug, title, summary, description, kind, start_at, end_at, location, external_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(['reunion-mensuelle-club', 'Réunion mensuelle du club', 'Rencontre mensuelle ouverte aux membres et visiteurs.', '<p>Réunion mensuelle ON4CRD avec actualités club, point technique, annonces et échanges conviviaux.</p>', 'club', date('Y-m-d 14:00:00', strtotime('+10 days')), date('Y-m-d 17:00:00', strtotime('+10 days')), 'Maison des Jeunes de Durnal', '', 'published']);

        file_put_contents($installLockFile, 'installed ' . date('c'));
        $message = 'Installation terminée. Le verrou a été créé : désactivez maintenant app.allow_install puis connectez-vous.';
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$content = '<div class="card"><h1>Installation ON4CRD v3.6.1</h1>';
if ($message !== '') {
    $content .= '<div class="flash flash-success">' . e($message) . '</div>';
}
if ($error !== '') {
    $content .= '<div class="flash flash-error">' . e($error) . '</div>';
}
$content .= '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><label>Indicatif admin<input type="text" name="callsign" value="ON4CRD" required></label><label>Nom complet<input type="text" name="full_name" value="Administrateur" required></label><label>Email<input type="email" name="email"></label><label>Mot de passe<input type="password" name="password" required></label><button class="button">Installer</button></form><p>Avant l’installation, copiez <code>config/config.sample.php</code> vers <code>config/config.php</code> et adaptez la connexion MySQL.</p></div>';

echo render_layout($content, 'Installation');
