-- Drop existing database (re-run safety)
DROP DATABASE IF EXISTS verkoopDit;
CREATE DATABASE verkoopDit
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE verkoopDit;

-- ---------------------------------------------------
-- Table 1: Users
-- Holds every account; one row per buyer/seller/adim.
-- Same user can buy/sell : controlled by 'role' column.
-- ---------------------------------------------------
CREATE TABLE users (
    user_id                 INT AUTO_INCREMENT PRIMARY KEY,
    username                VARCHAR(50) NOT NULL UNIQUE,
    email                   VARCHAR(100) NOT NULL UNIQUE,
    password_hash           VARCHAR(255) NOT NULL,
    first_name              VARCHAR(50),
    last_name               VARCHAR(50),
    phone                   VARCHAR(20), 
    profile_image           VARCHAR(255),
    location_user           VARCHAR(100),
    role_user               ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
    is_verified             BOOLEAN NOT NULL DEFAULT FALSE,
    verification_status     ENUM ('none', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'none',
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- --------------------------------------------------
-- Table 2: Categories
-- Table format for admins to add categories without changing the schema.
-- --------------------------------------------------
CREATE TABLE categories(
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    cat_name        VARCHAR(50) NOT NULL UNIQUE,
    icon            VARCHAR(50) 
);

INSERT INTO categories (cat_name) VALUES
    ('Electronics'),
    ('Clothing'),
    ('Furniture'),
    ('Books'),
    ('Sports'),
    ('Vehicles'),
    ('Home & Garden'),
    ('Beauty & Health'),
    ('Baby & Kids'),
    ('Collectibles'),
    ('Music & Instruments'),
    ('Business & Industrial'),
    ('Toys & Games'),
    ('Other');


-- -------------------------------------------------
-- Table 3: Products
-- -------------------------------------------------
CREATE TABLE products(
    product_id          INT AUTO_INCREMENT PRIMARY KEY,
    seller_id           INT NOT NULL,
    category_id         INT NOT NULL,
    prod_title          VARCHAR(150) NOT NULL,
    prod_description    TEXT,
    price               DECIMAL(10,2) NOT NULL,
    condition_status    ENUM('New', 'Like New', 'Good', 'Fair', 'Poor') NOT NULL DEFAULT 'Good',
    image_url           VARCHAR(255),
    location_user       VARCHAR(100),
    prod_status         ENUM('active', 'sold', 'pending', 'removed') NOT NULL DEFAULT 'active',
    views               INT NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),

    INDEX idx_seller (seller_id),
    INDEX idx_category (category_id),
    INDEX idx_status (prod_status)  
);

-- -------------------------------------------------
-- Table 4: Orders
-- -------------------------------------------------
CREATE TABLE orders (
    order_id                INT AUTO_INCREMENT PRIMARY KEY,
    product_id              INT NOT NULL,
    buyer_id                INT NOT NULL,
    seller_id               INT NOT NULL,
    prod_title              VARCHAR(150) NOT NULL,
    price                   DECIMAL (10,2) NOT NULL,
    buyer_name              VARCHAR(100),
    order_status            ENUM('awaiting_payment', 'paid', 'confirmed', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'awaiting_payment',
    delivery_address        TEXT,
    payment_method          VARCHAR(50) NULL,
    transaction_id          VARCHAR(50) NULL,
    paid_at                 TIMESTAMP NULL,
    notes                   TEXT,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id),

    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_status (order_status)
    );

-- ------------------------------------------------
-- Table 5: Messages
-- Buyer <> Seller chat, 'product_id' nullable because users can also message each other generally (not always about a specific listing).
-- ------------------------------------------------
CREATE TABLE messages (
    message_id        INT AUTO_INCREMENT PRIMARY KEY,
    product_id        INT NULL,
    sender_id         INT NOT NULL,
    receiver_id       INT NOT NULL,
    prod_title        VARCHAR(150),
    sender_name       VARCHAR(100),
    content           TEXT NOT NULL,
    is_read           BOOLEAN NOT NULL DEFAULT FALSE,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id),

    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_unread (receiver_id, is_read)
);

-- -------------------------------------------------
-- Table 6: Reviews
-- rating 1-5
-- -------------------------------------------------
CREATE TABLE reviews (
    review_id           INT AUTO_INCREMENT PRIMARY KEY,
    product_id          INT NOT NULL,
    seller_id           INT NOT NULL,
    reviewer_id         INT NOT NULL, 
    reviewer_name       VARCHAR(100),
    rating              TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment             TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id),

    INDEX idx_product (product_id),
    INDEX idx_seller (seller_id)
);

-- ---------------------------------------------
-- SAMPLE DATA
-- ---------------------------------------------

-- Sample Users:
-- NOTE: all passwords below are 'password123', hashed with PHP password_hash(). 
-- ---------------------------------------------
INSERT INTO users 
    (username, email, password_hash, first_name, last_name, phone, location_user, role_user, is_verified, verification_status)
VALUES
    ('admin', 'admin@verkoopdit.co.za', 'placeholder_hash', 'Site', 'Admin', '0820000000', 'Cape Town', 'admin', TRUE, 'approved'),
    ('lana', 'lana@verkoopdit.co.za', 'placeholder_hash', 'Lana', 'Dlamini', '0822222222', 'Soweto', 'seller', TRUE, 'approved'),
    ('nomsa', 'nomsa@verkoopdit.co.za', 'placeholder_hash', 'Nomsa', 'Dlamini', '0823333333', 'Durban', 'seller', FALSE, 'pending'),
    ('janco', 'janco@verkoopdit.co.za', 'placeholder_hash', 'Janco', 'Dlamini', '0824444444', 'Tyger Valley', 'buyer', FALSE, 'none');

-- Sample Products:
INSERT INTO products 
    (seller_id, category_id, prod_title, prod_description, price, condition_status, image_url, location_user, prod_status)
VALUES 
    (2, 1, 'Samsung Galaxy A14', 'Used Phone in great working condition, with charger and box.', 2500.00, 'Good', 'placeholder.jpg', 'Soweto', 'active'),
    (2, 2, 'Vintage Denim Jacket', 'Size M, oversized fit, no rips.', 250.00, 'Like New', 'placeholder.jpg', 'Soweto', 'active'),
    (3, 4, 'University textbooks x5', 'First-year IT textbooks, marked but readable.', 500.00, 'Fair', 'placeholder.jpg', 'Durban', 'active'),
    (3, 5, 'Mountain Bike (26-inch)', '21-speed, freshly serviced.', 1800.00, 'Good', 'placeholder.jpg', 'Durban', 'active');
    


-- Sample Messages:
INSERT INTO messages
    (product_id, sender_id, receiver_id, prod_title, sender_name, content, is_read)
VALUES
    (1, 4, 2, 'Samsung Galaxy A14', 'Janco Antero', 'Hi, is the phone still available? Can I see it in person?', FALSE),
    (3, 4, 3, 'University Textbooks x5', 'Janco Antero', 'Would you accept R350 for the textbooks?', FALSE);


-- Sample Orders:
INSERT INTO orders
    (product_id, buyer_id, seller_id, prod_title, price, buyer_name, order_status, delivery_address)
VALUES
    (2, 4, 2, 'Vintage Denim Jacket', 250.00, 'Janco Antero', 'paid', '12 Main Road, Tyger Valley, Cape Town, 7530');

--  Sample Reviews:
INSERT INTO reviews
    (product_id, seller_id, reviewer_id, reviewer_name, rating, comment)
VALUES
    (2, 2, 4, 'Janco Antero', 5, 'Quick delivery, jacket exactly as described. Thanks!');
    