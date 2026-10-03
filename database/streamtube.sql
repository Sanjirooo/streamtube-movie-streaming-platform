-- ============================================
-- Streamtube Database - Created 2024
-- ============================================

DROP DATABASE IF EXISTS streamtube;
CREATE DATABASE streamtube CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE streamtube;

-- ============================================
-- Table: membership_packages
-- ============================================
CREATE TABLE membership_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    price INT NOT NULL DEFAULT 0,
    duration_days INT NOT NULL DEFAULT 30,
    benefits TEXT NOT NULL,
    badge_color VARCHAR(30) DEFAULT '#e50914',
    comment_banner VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    avatar VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    membership_package_id INT DEFAULT NULL,
    membership_status ENUM('none', 'active') DEFAULT 'none',
    membership_started_at DATETIME DEFAULT NULL,
    membership_expired_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_package_id) REFERENCES membership_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Table: movies
-- ============================================
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    genre VARCHAR(100) NOT NULL,
    type ENUM('movie', 'series', 'trending') NOT NULL,
    year INT NOT NULL,
    synopsis TEXT,
    poster VARCHAR(500),
    banner VARCHAR(500),
    video_url VARCHAR(500),
    is_hero BOOLEAN DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table: hero_slides
-- ============================================
CREATE TABLE hero_slides (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT,
    title VARCHAR(255),
    subtitle TEXT,
    banner VARCHAR(500),
    slide_order INT DEFAULT 1,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Table: my_lists
-- ============================================
CREATE TABLE my_lists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie (user_id, movie_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: histories
-- ============================================
CREATE TABLE histories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_user_watched (user_id, watched_at)
) ENGINE=InnoDB;

-- ============================================
-- Table: comments
-- ============================================
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_movie_created (movie_id, created_at)
) ENGINE=InnoDB;

-- ============================================
-- Table: ratings
-- ============================================
CREATE TABLE ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie_rating (user_id, movie_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: membership_transactions
-- ============================================
CREATE TABLE membership_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_account_name VARCHAR(100) NOT NULL,
    payment_reference VARCHAR(100) NOT NULL,
    payment_note TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT DEFAULT NULL,
    verified_by INT DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES membership_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_membership_status (status),
    INDEX idx_membership_user (user_id)
) ENGINE=InnoDB;

-- ============================================
-- INSERT DEFAULT DATA
-- NOTE: Password hash di-generate dari password_hash()
-- Login demo: admin@streamtube.com / password123
-- Login demo: user@email.com / password123
-- ============================================

-- Membership Packages
INSERT INTO membership_packages (name, slug, price, duration_days, benefits, badge_color, comment_banner, sort_order, is_active) VALUES
('Bronze', 'bronze', 15000, 30, 'Nama tampil dengan badge Bronze\nAkses benefit dasar membership\nPrioritas komentar ringan', '#cd7f32', 'Member Bronze', 1, 1),
('Silver', 'silver', 30000, 30, 'Nama warna-warni dengan badge Silver\nBanner khusus saat komentar\nPrioritas komentar lebih terlihat', '#c0c0c0', 'Silver Member Banner', 2, 1),
('Gold', 'gold', 50000, 30, 'Badge Gold di profil dan komentar\nNama lebih menonjol\nBenefit eksklusif member Gold', '#ffd700', 'Gold Member Banner', 3, 1),
('Platinum', 'platinum', 75000, 30, 'Badge Platinum premium\nTulisan menonton sepuasnya\nBenefit paling lengkap di Streamtube', '#8de6ff', 'Platinum Unlimited Banner', 4, 1);

-- Admin Account (password: password123)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@streamtube.com', '$2y$12$Z93EMuJl4VAjHpEX95uzc.JYIjwkBfIJa//Z39CNHzTikIv22Z50e', 'admin');

-- Regular Users (password: password123)
INSERT INTO users (name, email, password, role, membership_package_id, membership_status, membership_started_at, membership_expired_at) VALUES
('User Demo', 'user@email.com', '$2y$12$Z93EMuJl4VAjHpEX95uzc.JYIjwkBfIJa//Z39CNHzTikIv22Z50e', 'user', NULL, 'none', NULL, NULL),
('Budi Santoso', 'budi@email.com', '$2y$12$Z93EMuJl4VAjHpEX95uzc.JYIjwkBfIJa//Z39CNHzTikIv22Z50e', 'user', 1, 'active', NOW() - INTERVAL 3 DAY, NOW() + INTERVAL 27 DAY),
('Siti Rahayu', 'siti@email.com', '$2y$12$Z93EMuJl4VAjHpEX95uzc.JYIjwkBfIJa//Z39CNHzTikIv22Z50e', 'user', NULL, 'none', NULL, NULL),
('Ahmad Fauzi', 'ahmad@email.com', '$2y$12$Z93EMuJl4VAjHpEX95uzc.JYIjwkBfIJa//Z39CNHzTikIv22Z50e', 'user', NULL, 'none', NULL, NULL);

-- Movies Data
INSERT INTO movies (title, genre, type, year, synopsis, poster, banner, video_url, is_hero, view_count) VALUES

-- TRENDING / HERO MOVIES
('The Last Kingdom', 'Action, Drama, History', 'trending', 2022, 'Menceritakan kisah Uhtred, seorang Saxon yang dibesarkan oleh orang-orang Denmark setelah orphaned. Ia berjuang untuk memenuhi tujuan hidupnya dan mendapatkan kembali tanah leluhurnya.', 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=400', 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1920', 'https://www.youtube.com/embed', 1, 15234),

('Stranger Things', 'Sci-Fi, Horror, Drama', 'trending', 2024, 'Quando a jovem Will desaparece, sua mãe, o chefe de polícia e seus amigos tentam entender o que aconteceu.', 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=400', 'https://images.unsplash.com/photo-1535016120720-40c646be5580?w=1920', 'https://www.youtube.com/embed', 1, 23456),

('Wednesday', 'Comedy, Mystery, Horror', 'trending', 2022, 'Follows Wednesday Addams series of murders at Nevermore Academy.', 'https://images.unsplash.com/photo-1600980697393-7f3277f6f24e?w=400', 'https://images.unsplash.com/photo-1600093463592-8e36ae240ef3?w=1920', 'https://www.youtube.com/embed', 1, 18976),

-- MOVIES
('Dune', 'Sci-Fi, Adventure', 'movie', 2021, 'A noble family becomes embroiled in a war for control over the galaxy most valuable asset.', 'https://images.unsplash.com/photo-1544441893-675973e31985?w=400', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1920', 'https://www.youtube.com/embed', 0, 8765),

('The Batman', 'Action, Crime, Drama', 'movie', 2022, 'When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate.', 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?w=400', 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1920', 'https://www.youtube.com/embed', 0, 12340),

('Spider-Man: No Way Home', 'Action, Adventure, Sci-Fi', 'movie', 2021, 'With Spider identity revealed, Peter asks Doctor Strange for help.', 'https://images.unsplash.com/photo-1534809027769-b00d7a0f9d6f?w=400', 'https://images.unsplash.com/photo-1518676590747-1e3d2a04c9a0?w=1920', 'https://www.youtube.com/embed', 0, 19234),

('The Dark Knight', 'Action, Crime, Drama', 'movie', 2008, 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham.', 'https://images.unsplash.com/photo-1506461883276-70415253a80?w=400', 'https://images.unsplash.com/photo-1533231432538-c9152fb0a4a2?w=1920', 'https://www.youtube.com/embed', 0, 28765),

('Oppenheimer', 'Biography, Drama, History', 'movie', 2023, 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400', 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920', 'https://www.youtube.com/embed', 0, 14532),

('Inception', 'Action, Adventure, Sci-Fi', 'movie', 2010, 'A thief who steals corporate secrets through dream-sharing technology is given the inverse task.', 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=400', 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920', 'https://www.youtube.com/embed', 0, 21345),

('Interstellar', 'Adventure, Drama, Sci-Fi', 'movie', 2014, 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity survival.', 'https://images.unsplash.com/photo-1534996858221-380b92700493?w=400', 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920', 'https://www.youtube.com/embed', 0, 25678),

('Avengers: Endgame', 'Action, Adventure, Drama', 'movie', 2019, 'After devastating events, the Avengers assemble once more to reverse Thanos actions.', 'https://images.unsplash.com/photo-1624996756521-4afe3d8b176d?w=400', 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?w=1920', 'https://www.youtube.com/embed', 0, 29876),

('Joker', 'Crime, Drama, Thriller', 'movie', 2019, 'In Gotham City, mentally troubled comedian Arthur Fleck is disregarded and mistreated by society.', 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=400', 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1920', 'https://www.youtube.com/embed', 0, 17890),

-- SERIES
('Breaking Bad', 'Crime, Drama, Thriller', 'series', 2013, 'A high school chemistry teacher diagnosed with lung cancer turns to manufacturing methamphetamine.', 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=400', 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1920', 'https://www.youtube.com/embed', 0, 32456),

('Game of Thrones', 'Action, Adventure, Drama', 'series', 2019, 'Nine noble families fight for control over the lands of Westeros, while an ancient enemy returns.', 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=400', 'https://images.unsplash.com/photo-1534809027769-b00d7a0f9d6f?w=1920', 'https://www.youtube.com/embed', 0, 45678),

('The Witcher', 'Action, Adventure, Fantasy', 'series', 2023, 'Geralt of Rivia, a mutated monster-hunter for hire, struggles to find his place in a world where people often prove more wicked than beasts.', 'https://images.unsplash.com/photo-1544441893-675973e31985?w=400', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1920', 'https://www.youtube.com/embed', 0, 23456),

('Money Heist', 'Action, Crime, Drama', 'series', 2021, 'Eight thieves take hostages and lock themselves in the Royal Mint of Spain as a criminal mastermind manipulates the police.', 'https://images.unsplash.com/photo-1600980697393-7f3277f6f24e?w=400', 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1920', 'https://www.youtube.com/embed', 0, 28901),

('House of Dragon', 'Action, Adventure, Fantasy', 'series', 2022, 'The internal succession war within House Targaryen at the end of its reign.', 'https://images.unsplash.com/photo-1535016120720-40c646be5580?w=400', 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?w=1920', 'https://www.youtube.com/embed', 0, 19876),

('Squid Game', 'Action, Drama, Mystery', 'series', 2021, 'Hundreds of cash-strapped players accept a strange invitation to compete in children games.', 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=400', 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1920', 'https://www.youtube.com/embed', 0, 35678),

('The Office', 'Comedy', 'series', 2013, 'A mockumentary on a group of typical office workers, where the workday consists of ego clashes.', 'https://images.unsplash.com/photo-1534996858221-380b92700493?w=400', 'https://images.unsplash.com/photo-1534809027769-b00d7a0f9d6f?w=1920', 'https://www.youtube.com/embed', 0, 12345),

('Friends', 'Comedy, Romance', 'series', 2004, 'Follows the personal and professional lives of six twenty to thirty-something-year-old friends.', 'https://images.unsplash.com/photo-1518676590747-1e3d2a04c9a0?w=400', 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=1920', 'https://www.youtube.com/embed', 0, 34567),

-- More TRENDING
('Gladiator', 'Action, Adventure, Drama', 'trending', 2024, 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family.', 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=400', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1920', 'https://www.youtube.com/embed', 0, 15678),

('Avatar: The Way of Water', 'Action, Adventure, Fantasy', 'trending', 2022, 'Jake Sully and Neytiri form a family and do everything to protect each other.', 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400', 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920', 'https://www.youtube.com/embed', 0, 28765);

-- Hero Slides
INSERT INTO hero_slides (movie_id, title, subtitle, banner, slide_order, is_active) VALUES
(1, 'The Last Kingdom', 'Pertarungan untuk tahta dan kehormatan. Saksikan kisah epik Uhtred dalam memulihkan tanah Saxon.', 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1920', 1, 1),
(2, 'Stranger Things', 'Kejutan yang mengerikan datang dari dimensi lain. Apakah kamu siap?', 'https://images.unsplash.com/photo-1535016120720-40c646be5580?w=1920', 2, 1),
(3, 'Wednesday', 'Wednesday Addams kembali dengan teka-teki murder mystery yang menegangkan!', 'https://images.unsplash.com/photo-1600093463592-8e36ae240ef3?w=1920', 3, 1);

-- Sample My Lists
INSERT INTO my_lists (user_id, movie_id) VALUES
(2, 1), (2, 4), (2, 6),
(3, 2), (3, 8), (3, 13),
(4, 3), (4, 7), (4, 14);

-- Sample Histories
INSERT INTO histories (user_id, movie_id, watched_at) VALUES
(2, 1, NOW() - INTERVAL 2 DAY),
(2, 4, NOW() - INTERVAL 5 DAY),
(2, 6, NOW() - INTERVAL 1 DAY),
(3, 2, NOW() - INTERVAL 3 DAY),
(3, 13, NOW() - INTERVAL 7 DAY),
(4, 3, NOW() - INTERVAL 4 DAY),
(4, 14, NOW() - INTERVAL 6 DAY);

-- Sample Comments
INSERT INTO comments (user_id, movie_id, comment) VALUES
(2, 1, 'Film ini luar biasa! Ceritanya sangat menarik dan aksi yang luar biasa.'),
(2, 4, 'Visual yang memukau. Christopher Nolan memang master!'),
(3, 2, 'Serie ini bikin ketagihan! Episode selanjutnya kapan?'),
(3, 13, 'Walter White adalah karakter paling kompleks yang pernah saya lihat.'),
(4, 3, 'Wednesday Addams pergi, tapi vibes nya tetap keren!'),
(4, 14, 'Game of Thrones adalah salah satu serie terbaik sepanjang masa.');

-- Sample Ratings
INSERT INTO ratings (user_id, movie_id, rating) VALUES
(2, 1, 5), (2, 4, 5), (2, 6, 4),
(3, 2, 5), (3, 8, 4), (3, 13, 5),
(4, 3, 4), (4, 7, 5), (4, 14, 5);

-- Sample Membership Transactions
INSERT INTO membership_transactions (user_id, package_id, amount, payment_method, payment_account_name, payment_reference, payment_note, status, admin_note, verified_by, verified_at, created_at) VALUES
(3, 1, 15000, 'E-Wallet', 'Budi Santoso', 'TRX-BRONZE-001', 'Pembayaran membership Bronze.', 'approved', 'Pembayaran valid.', 1, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 3 DAY),
(4, 2, 30000, 'Bank Transfer', 'Siti Rahayu', 'TRX-SILVER-002', 'Mohon dicek admin.', 'pending', NULL, NULL, NULL, NOW() - INTERVAL 1 HOUR);

