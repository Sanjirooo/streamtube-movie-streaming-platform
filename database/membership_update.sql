-- ============================================
-- Streamtube Membership Update / Migration
-- Jalankan file ini kalau database streamtube lama sudah ada.
-- Kalau import ulang dari awal, cukup pakai streamtube.sql.
-- ============================================
USE streamtube;

CREATE TABLE IF NOT EXISTS membership_packages (
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

INSERT INTO membership_packages (name, slug, price, duration_days, benefits, badge_color, comment_banner, sort_order, is_active) VALUES
('Bronze', 'bronze', 15000, 30, 'Nama tampil dengan badge Bronze\nAkses benefit dasar membership\nPrioritas komentar ringan', '#cd7f32', 'Member Bronze', 1, 1),
('Silver', 'silver', 30000, 30, 'Nama warna-warni dengan badge Silver\nBanner khusus saat komentar\nPrioritas komentar lebih terlihat', '#c0c0c0', 'Silver Member Banner', 2, 1),
('Gold', 'gold', 50000, 30, 'Badge Gold di profil dan komentar\nNama lebih menonjol\nBenefit eksklusif member Gold', '#ffd700', 'Gold Member Banner', 3, 1),
('Platinum', 'platinum', 75000, 30, 'Badge Platinum premium\nTulisan menonton sepuasnya\nBenefit paling lengkap di Streamtube', '#8de6ff', 'Platinum Unlimited Banner', 4, 1)
ON DUPLICATE KEY UPDATE
    price = VALUES(price),
    duration_days = VALUES(duration_days),
    benefits = VALUES(benefits),
    badge_color = VALUES(badge_color),
    comment_banner = VALUES(comment_banner),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

ALTER TABLE users
    ADD COLUMN membership_package_id INT DEFAULT NULL,
    ADD COLUMN membership_status ENUM('none', 'active') DEFAULT 'none',
    ADD COLUMN membership_started_at DATETIME DEFAULT NULL,
    ADD COLUMN membership_expired_at DATETIME DEFAULT NULL;

ALTER TABLE users
    ADD CONSTRAINT fk_users_membership_package
    FOREIGN KEY (membership_package_id) REFERENCES membership_packages(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS membership_transactions (
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

-- Contoh data: Budi Santoso aktif Bronze kalau user ini ada di database.
UPDATE users u
JOIN membership_packages mp ON mp.slug = 'bronze'
SET u.membership_package_id = mp.id,
    u.membership_status = 'active',
    u.membership_started_at = IFNULL(u.membership_started_at, NOW() - INTERVAL 3 DAY),
    u.membership_expired_at = IFNULL(u.membership_expired_at, NOW() + INTERVAL 27 DAY)
WHERE u.email = 'budi@email.com';
