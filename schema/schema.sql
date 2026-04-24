CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auth_user_id INT UNSIGNED DEFAULT NULL UNIQUE,
    callsign VARCHAR(32) NOT NULL UNIQUE,
    full_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    qth VARCHAR(190) DEFAULT NULL,
    locator VARCHAR(32) DEFAULT NULL,
    phone VARCHAR(64) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    licence_class VARCHAR(64) DEFAULT NULL,
    operator_since VARCHAR(32) DEFAULT NULL,
    cq_zone VARCHAR(16) DEFAULT NULL,
    itu_zone VARCHAR(16) DEFAULT NULL,
    qsl_via VARCHAR(190) DEFAULT NULL,
    lotw_username VARCHAR(190) DEFAULT NULL,
    eqsl_username VARCHAR(190) DEFAULT NULL,
    qrz_url VARCHAR(255) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    station_equipment TEXT DEFAULT NULL,
    antennas TEXT DEFAULT NULL,
    max_power VARCHAR(64) DEFAULT NULL,
    favourite_bands VARCHAR(190) DEFAULT NULL,
    favourite_modes VARCHAR(190) DEFAULT NULL,
    interests TEXT DEFAULT NULL,
    is_committee TINYINT(1) NOT NULL DEFAULT 0,
    committee_role VARCHAR(190) DEFAULT NULL,
    committee_bio TEXT DEFAULT NULL,
    committee_sort_order INT NOT NULL DEFAULT 100,
    visibility_email ENUM('public','members','private') NOT NULL DEFAULT 'members',
    visibility_phone ENUM('public','members','private') NOT NULL DEFAULT 'private',
    visibility_full_name ENUM('public','members','private') NOT NULL DEFAULT 'members',
    visibility_qth ENUM('public','members','private') NOT NULL DEFAULT 'members',
    visibility_licence_class ENUM('public','members','private') NOT NULL DEFAULT 'members',
    visibility_favourite_bands ENUM('public','members','private') NOT NULL DEFAULT 'members',
    visibility_station ENUM('public','members','private') NOT NULL DEFAULT 'members',
    visibility_online ENUM('public','members','private') NOT NULL DEFAULT 'members',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(249) NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(100) DEFAULT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verified TINYINT UNSIGNED NOT NULL DEFAULT 0,
    resettable TINYINT UNSIGNED NOT NULL DEFAULT 1,
    roles_mask INT UNSIGNED NOT NULL DEFAULT 0,
    registered INT UNSIGNED NOT NULL,
    last_login INT UNSIGNED DEFAULT NULL,
    force_logout MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_username_unique (username)
);

CREATE TABLE IF NOT EXISTS users_confirmations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(249) NOT NULL,
    selector VARCHAR(16) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires INT UNSIGNED NOT NULL,
    UNIQUE KEY users_confirmations_selector_unique (selector),
    KEY users_confirmations_user_id_index (user_id)
);

CREATE TABLE IF NOT EXISTS users_remembered (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user INT UNSIGNED NOT NULL,
    selector VARCHAR(24) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires INT UNSIGNED NOT NULL,
    UNIQUE KEY users_remembered_selector_unique (selector),
    KEY users_remembered_user_index (user)
);

CREATE TABLE IF NOT EXISTS users_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user INT UNSIGNED NOT NULL,
    selector VARCHAR(20) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires INT UNSIGNED NOT NULL,
    UNIQUE KEY users_resets_selector_unique (selector),
    KEY users_resets_user_index (user)
);

CREATE TABLE IF NOT EXISTS users_throttling (
    bucket VARCHAR(44) NOT NULL,
    tokens FLOAT UNSIGNED NOT NULL,
    replenished_at INT UNSIGNED NOT NULL,
    expires_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (bucket)
);

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(190) NOT NULL
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(120) NOT NULL UNIQUE,
    label VARCHAR(190) NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE IF NOT EXISTS member_roles (
    member_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (member_id, role_id)
);

CREATE TABLE IF NOT EXISTS member_permissions (
    member_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (member_id, permission_id)
);

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    is_core TINYINT(1) NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS news_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS news_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    author_id INT DEFAULT NULL,
    moderator_id INT DEFAULT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    excerpt TEXT DEFAULT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','pending','published','rejected') NOT NULL DEFAULT 'draft',
    moderation_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS news_section_managers (
    member_id INT NOT NULL,
    section_id INT NOT NULL,
    PRIMARY KEY (member_id, section_id)
);

CREATE TABLE IF NOT EXISTS dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    widget_key VARCHAR(80) NOT NULL,
    position INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS qso_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    qso_call VARCHAR(64) NOT NULL,
    qso_date VARCHAR(32) DEFAULT NULL,
    time_on VARCHAR(32) DEFAULT NULL,
    band VARCHAR(32) DEFAULT NULL,
    mode VARCHAR(32) DEFAULT NULL,
    rst_sent VARCHAR(16) DEFAULT NULL,
    rst_recv VARCHAR(16) DEFAULT NULL,
    raw_payload LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS qsl_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    qso_call VARCHAR(64) DEFAULT NULL,
    qso_date VARCHAR(32) DEFAULT NULL,
    time_on VARCHAR(32) DEFAULT NULL,
    band VARCHAR(32) DEFAULT NULL,
    mode VARCHAR(32) DEFAULT NULL,
    rst_sent VARCHAR(16) DEFAULT NULL,
    rst_recv VARCHAR(16) DEFAULT NULL,
    template_name VARCHAR(64) DEFAULT 'classic',
    svg_content LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    excerpt TEXT DEFAULT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    author_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS wiki_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    content LONGTEXT NOT NULL,
    author_id INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS wiki_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wiki_page_id INT NOT NULL,
    member_id INT DEFAULT NULL,
    content LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS album_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    caption TEXT DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS chatbot_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT DEFAULT NULL,
    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,
    source_name VARCHAR(190) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS ad_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_member_id INT NOT NULL,
    placement_id INT NOT NULL,
    format_code VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    target_url VARCHAR(255) DEFAULT NULL,
    start_at DATETIME DEFAULT NULL,
    duration_days INT DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    max_impressions INT DEFAULT NULL,
    weight INT NOT NULL DEFAULT 100,
    status ENUM('draft','pending','active','paused','expired','rejected') NOT NULL DEFAULT 'draft',
    moderation_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ad_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    event_type ENUM('impression','click') NOT NULL,
    placement_code VARCHAR(100) NOT NULL,
    member_id INT DEFAULT NULL,
    ip_hash VARCHAR(64) DEFAULT NULL,
    user_agent_hash VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ad_events_ad_id (ad_id),
    INDEX idx_ad_events_type (event_type),
    INDEX idx_ad_events_created_at (created_at)
);


CREATE TABLE IF NOT EXISTS press_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(190) NOT NULL,
    role_label VARCHAR(190) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(64) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS press_releases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    summary TEXT DEFAULT NULL,
    published_on DATE DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS editorial_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_key VARCHAR(190) NOT NULL UNIQUE,
    fr_text LONGTEXT NOT NULL,
    en_text LONGTEXT DEFAULT NULL,
    de_text LONGTEXT DEFAULT NULL,
    nl_text LONGTEXT DEFAULT NULL,
    en_status ENUM('missing','auto','reviewed','stale') NOT NULL DEFAULT 'missing',
    de_status ENUM('missing','auto','reviewed','stale') NOT NULL DEFAULT 'missing',
    nl_status ENUM('missing','auto','reviewed','stale') NOT NULL DEFAULT 'missing',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS news_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_post_id INT NOT NULL,
    locale CHAR(2) NOT NULL,
    source_hash CHAR(40) NOT NULL,
    title TEXT DEFAULT NULL,
    excerpt MEDIUMTEXT DEFAULT NULL,
    content LONGTEXT DEFAULT NULL,
    status ENUM('missing','auto','needs_review','reviewed') NOT NULL DEFAULT 'missing',
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_news_translation (news_post_id, locale)
);

CREATE TABLE IF NOT EXISTS article_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    locale CHAR(2) NOT NULL,
    source_hash CHAR(40) NOT NULL,
    title TEXT DEFAULT NULL,
    excerpt MEDIUMTEXT DEFAULT NULL,
    content LONGTEXT DEFAULT NULL,
    status ENUM('missing','auto','needs_review','reviewed') NOT NULL DEFAULT 'missing',
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_article_translation (article_id, locale)
);

CREATE TABLE IF NOT EXISTS live_feeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(190) NOT NULL,
    url VARCHAR(255) DEFAULT NULL,
    parser VARCHAR(80) NOT NULL DEFAULT 'json',
    cache_ttl INT NOT NULL DEFAULT 900,
    refresh_seconds INT NOT NULL DEFAULT 900,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    summary TEXT DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    kind ENUM('club','contest') NOT NULL DEFAULT 'club',
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    location VARCHAR(190) DEFAULT NULL,
    external_url VARCHAR(255) DEFAULT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS shop_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shop_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    summary TEXT DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    price_cents INT NOT NULL DEFAULT 0,
    stock_qty INT DEFAULT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_shop_products_status (status)
);

CREATE TABLE IF NOT EXISTS shop_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    reference_code VARCHAR(40) NOT NULL UNIQUE,
    status ENUM('pending','confirmed','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
    payment_method ENUM('on_site','bank_transfer') NOT NULL DEFAULT 'on_site',
    total_cents INT NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shop_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_title VARCHAR(190) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price_cents INT NOT NULL DEFAULT 0,
    line_total_cents INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_shop_order_items_order_id (order_id),
    INDEX idx_shop_order_items_product_id (product_id)
);

CREATE TABLE IF NOT EXISTS auction_lots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(190) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    summary TEXT DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    starting_price_cents INT NOT NULL DEFAULT 0,
    reserve_price_cents INT DEFAULT NULL,
    min_increment_cents INT NOT NULL DEFAULT 100,
    buy_now_price_cents INT DEFAULT NULL,
    current_price_cents INT NOT NULL DEFAULT 0,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    extended_until DATETIME DEFAULT NULL,
    status ENUM('draft','scheduled','active','closed','cancelled') NOT NULL DEFAULT 'draft',
    winner_member_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_auction_lots_status_ends_at (status, ends_at)
);

CREATE TABLE IF NOT EXISTS auction_bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lot_id INT NOT NULL,
    member_id INT NOT NULL,
    amount_cents INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auction_bids_lot_amount (lot_id, amount_cents, created_at)
);

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    member_id INT DEFAULT NULL,
    status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
    source VARCHAR(32) NOT NULL DEFAULT 'admin',
    subscribe_token CHAR(48) NOT NULL,
    unsubscribe_token CHAR(48) NOT NULL,
    unsubscribed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_newsletter_member (member_id),
    INDEX idx_newsletter_status (status)
);

CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','sent') NOT NULL DEFAULT 'draft',
    created_by INT DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS newsletter_deliveries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    subscriber_id INT NOT NULL,
    status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    error_message VARCHAR(255) DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_newsletter_delivery (campaign_id, subscriber_id),
    INDEX idx_newsletter_delivery_status (status)
);

CREATE TABLE IF NOT EXISTS dinner_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserved_by VARCHAR(190) NOT NULL,
    total_cents INT NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dinner_reservation_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    starter_code VARCHAR(64) DEFAULT NULL,
    starter_label VARCHAR(190) DEFAULT NULL,
    starter_price_cents INT NOT NULL DEFAULT 0,
    meal_code VARCHAR(64) NOT NULL,
    meal_label VARCHAR(190) NOT NULL,
    meal_price_cents INT NOT NULL,
    dessert_code VARCHAR(64) NOT NULL,
    dessert_label VARCHAR(190) NOT NULL,
    dessert_price_cents INT NOT NULL,
    starter_enabled TINYINT(1) NOT NULL DEFAULT 0,
    meal_enabled TINYINT(1) NOT NULL DEFAULT 1,
    dessert_enabled TINYINT(1) NOT NULL DEFAULT 1,
    quantity INT NOT NULL DEFAULT 1,
    line_total_cents INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dinner_reservation_id (reservation_id),
    FOREIGN KEY (reservation_id) REFERENCES dinner_reservations(id) ON DELETE CASCADE
);
