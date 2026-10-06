-- =====================================================================
--  FC Scorpio — database schema + demo data
--  Import in phpMyAdmin (Import tab) or run:  mysql -u root < database/schema.sql
--  Re-importing drops and recreates everything.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS football_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE football_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items, orders, ticket_orders, ticket_types, fixtures, products, players, users,
                     members, player, match_schedule, tickets;   -- old tables from v1
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Users (fans and club staff share one table; `role` decides access)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('fan','admin') NOT NULL DEFAULT 'fan',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Squad
-- ---------------------------------------------------------------------
CREATE TABLE players (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    position      ENUM('Goalkeeper','Defender','Midfielder','Forward') NOT NULL,
    jersey_number TINYINT UNSIGNED NOT NULL UNIQUE,
    date_of_birth DATE NULL,
    nationality   VARCHAR(60) NOT NULL DEFAULT 'Bangladesh',
    appearances   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    goals         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    assists       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    photo         VARCHAR(255) NULL,
    is_captain    TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Fixtures & results
-- ---------------------------------------------------------------------
CREATE TABLE fixtures (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opponent      VARCHAR(100) NOT NULL,
    competition   VARCHAR(80)  NOT NULL DEFAULT 'Premier Division',
    venue         VARCHAR(120) NOT NULL,
    is_home       TINYINT(1)   NOT NULL DEFAULT 1,
    kickoff       DATETIME     NOT NULL,
    status        ENUM('scheduled','finished','postponed') NOT NULL DEFAULT 'scheduled',
    goals_for     TINYINT UNSIGNED NULL,
    goals_against TINYINT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kickoff (kickoff)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tickets: each fixture has seat categories with their own price & capacity
-- ---------------------------------------------------------------------
CREATE TABLE ticket_types (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fixture_id  INT UNSIGNED NOT NULL,
    category    ENUM('Premium','Standard','Economy') NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    capacity    INT UNSIGNED  NOT NULL,
    sold        INT UNSIGNED  NOT NULL DEFAULT 0,
    UNIQUE KEY uq_fixture_category (fixture_id, category),
    CONSTRAINT fk_tt_fixture FOREIGN KEY (fixture_id) REFERENCES fixtures(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ticket_orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference      VARCHAR(20)  NOT NULL UNIQUE,
    user_id        INT UNSIGNED NOT NULL,
    ticket_type_id INT UNSIGNED NOT NULL,
    quantity       SMALLINT UNSIGNED NOT NULL,
    unit_price     DECIMAL(10,2) NOT NULL,
    total          DECIMAL(10,2) NOT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_to_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_to_type FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Fan store
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    category    ENUM('Kits','Training','Accessories','Souvenirs') NOT NULL DEFAULT 'Accessories',
    description TEXT NULL,
    price       DECIMAL(10,2) NOT NULL,
    stock       INT UNSIGNED  NOT NULL DEFAULT 0,
    image       VARCHAR(255) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference        VARCHAR(20)  NOT NULL UNIQUE,
    user_id          INT UNSIGNED NOT NULL,
    total            DECIMAL(10,2) NOT NULL,
    status           ENUM('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    shipping_name    VARCHAR(100) NOT NULL,
    shipping_phone   VARCHAR(20)  NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(120) NOT NULL,           -- snapshot, survives product deletion
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     SMALLINT UNSIGNED NOT NULL,
    CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
--  Demo data
--  Admin:  admin@fcscorpio.com / Admin@123
--  Fan:    fan@fcscorpio.com   / Fan@12345
-- =====================================================================

INSERT INTO users (name, email, phone, password_hash, role) VALUES
('Club Admin',    'admin@fcscorpio.com', '01700000000', '$2y$12$b6VDsWsEedfMu7xdzMwHu.b5EpeSUyY9Bh1GHptPqxBpF4FaM6xHi', 'admin'),
('Rafiq Hasan',   'fan@fcscorpio.com',   '01800000000', '$2y$12$V7DaXLx0Bv8XidXFHSIKdOoY6YYic.oIKJ3BqsNHNJTDpIiCihO9y', 'fan');

INSERT INTO players (name, position, jersey_number, date_of_birth, nationality, appearances, goals, assists, is_captain) VALUES
('Arman Chowdhury',   'Goalkeeper', 1,  '1996-03-14', 'Bangladesh', 24, 0, 0, 0),
('Sabbir Rahman',     'Goalkeeper', 22, '2001-08-02', 'Bangladesh', 6,  0, 0, 0),
('Tanvir Ahmed',      'Defender',   2,  '1999-11-21', 'Bangladesh', 22, 1, 3, 0),
('Mehedi Hasan',      'Defender',   4,  '1995-05-09', 'Bangladesh', 25, 3, 1, 1),
('Nayeem Islam',      'Defender',   5,  '1998-01-30', 'Bangladesh', 21, 2, 0, 0),
('Joel Mensah',       'Defender',   3,  '1997-07-17', 'Ghana',      19, 0, 2, 0),
('Rakib Hossain',     'Defender',   15, '2003-04-05', 'Bangladesh', 9,  0, 1, 0),
('Fahim Karim',       'Midfielder', 6,  '1998-09-12', 'Bangladesh', 23, 2, 5, 0),
('Imran Sheikh',      'Midfielder', 8,  '2000-02-26', 'Bangladesh', 24, 4, 7, 0),
('Lucas Ferreira',    'Midfielder', 10, '1996-12-03', 'Brazil',     25, 9, 11,0),
('Shakil Mahmud',     'Midfielder', 14, '2002-06-18', 'Bangladesh', 15, 1, 3, 0),
('Rifat Alam',        'Midfielder', 16, '2004-10-10', 'Bangladesh', 8,  1, 1, 0),
('Sohel Rana',        'Forward',    7,  '1999-03-28', 'Bangladesh', 24, 8, 4, 0),
('Kwame Boateng',     'Forward',    9,  '1997-08-08', 'Ghana',      23, 14, 3, 0),
('Nahid Parvez',      'Forward',    11, '2001-01-15', 'Bangladesh', 20, 6, 6, 0),
('Asif Iqbal',        'Forward',    19, '2005-05-22', 'Bangladesh', 7,  2, 0, 0);

-- Dates are relative to the import date so the demo always has upcoming matches.
INSERT INTO fixtures (opponent, competition, venue, is_home, kickoff, status, goals_for, goals_against) VALUES
('Dhaka Falcons',        'Premier Division', 'Scorpio Arena, Dhaka',       1, TIMESTAMP(CURDATE() - INTERVAL 24 DAY, '19:00:00'), 'finished', 3, 1),
('Chattogram Mariners',  'Premier Division', 'Mariners Ground, Chattogram',0, TIMESTAMP(CURDATE() - INTERVAL 17 DAY, '16:30:00'), 'finished', 1, 1),
('Sylhet Tigers',        'Federation Cup',   'Scorpio Arena, Dhaka',       1, TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '19:00:00'), 'finished', 2, 0),
('Rajshahi Royals',      'Premier Division', 'Royals Park, Rajshahi',      0, TIMESTAMP(CURDATE() - INTERVAL 3 DAY,  '17:00:00'), 'finished', 0, 1),
('Khulna Titans',        'Premier Division', 'Scorpio Arena, Dhaka',       1, TIMESTAMP(CURDATE() + INTERVAL 4 DAY,  '19:30:00'), 'scheduled', NULL, NULL),
('Barishal Blazers',     'Federation Cup',   'Blazers Stadium, Barishal',  0, TIMESTAMP(CURDATE() + INTERVAL 11 DAY, '16:00:00'), 'scheduled', NULL, NULL),
('Cumilla Warriors',     'Premier Division', 'Scorpio Arena, Dhaka',       1, TIMESTAMP(CURDATE() + INTERVAL 18 DAY, '19:00:00'), 'scheduled', NULL, NULL),
('Rangpur Rangers',      'Premier Division', 'Rangers Field, Rangpur',     0, TIMESTAMP(CURDATE() + INTERVAL 25 DAY, '17:30:00'), 'scheduled', NULL, NULL),
('Mymensingh United',    'Premier Division', 'Scorpio Arena, Dhaka',       1, TIMESTAMP(CURDATE() + INTERVAL 32 DAY, '19:30:00'), 'scheduled', NULL, NULL);

INSERT INTO ticket_types (fixture_id, category, price, capacity, sold) VALUES
(5, 'Premium', 1500, 200,  142), (5, 'Standard', 700, 800, 516), (5, 'Economy', 300, 1500, 1498),
(7, 'Premium', 1500, 200,   38), (7, 'Standard', 700, 800, 121), (7, 'Economy', 300, 1500, 402),
(9, 'Premium', 1800, 200,    0), (9, 'Standard', 800, 800,  12), (9, 'Economy', 350, 1500, 40);

INSERT INTO products (name, category, description, price, stock, image) VALUES
('Home Jersey 26/27',     'Kits',        'The official gold home shirt with teal sleeves. Breathable, lightweight match fabric.', 2490, 60, 'home-jersey.svg'),
('Away Jersey 26/27',     'Kits',        'Midnight navy away shirt with a gold centre stripe. Worn on the road all season.',       2490, 45, 'away-jersey.svg'),
('Training Top',          'Training',    'The teal training top the squad wears at every session. Quick-dry and stretchy.',       1650, 30, 'training-top.svg'),
('Supporters Scarf',      'Accessories', 'Knitted gold, navy and teal scarf. Made for match nights in the stands.',                 650,  120,'scarf.svg'),
('Club Cap',              'Accessories', 'Navy six-panel cap with an embroidered crest and gold peak.',                          550,  8,  'cap.svg'),
('Official Match Ball',   'Souvenirs',   'Size 5 match ball in club colours. Thermo-bonded panels for a true flight.',           1990, 25, 'match-ball.svg'),
('Crest Mug',             'Souvenirs',   'Ceramic 350 ml mug with the Scorpio crest. Microwave and dishwasher safe.',            450,  0,  'mug.svg'),
('Stadium Flag',          'Souvenirs',   'Large 90×150 cm flag for home games, balconies and road trips.',                       890,  40, 'flag.svg');

INSERT INTO ticket_orders (reference, user_id, ticket_type_id, quantity, unit_price, total, created_at) VALUES
('TKT-3F9A21C', 2, 2, 2, 700, 1400, NOW() - INTERVAL 2 DAY),
('TKT-81B0E4D', 2, 4, 1, 1500, 1500, NOW() - INTERVAL 1 DAY);

INSERT INTO orders (reference, user_id, total, status, shipping_name, shipping_phone, shipping_address, created_at) VALUES
('ORD-5C2E7A9', 2, 3140, 'shipped', 'Rafiq Hasan', '01800000000', 'House 12, Road 4, Mohammadpur, Dhaka', NOW() - INTERVAL 5 DAY);
INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES
(1, 1, 'Home Jersey 26/27', 2490, 1),
(1, 4, 'Supporters Scarf',  650,  1);
