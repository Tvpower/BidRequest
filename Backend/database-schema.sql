-- BidRequest Database Schema

-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS request_details;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS sellers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Create tables according to the relational schema

-- Users table
CREATE TABLE users (
                     user_id INT AUTO_INCREMENT PRIMARY KEY,
                     username VARCHAR(100) NOT NULL,
                     email VARCHAR(255) NOT NULL UNIQUE,
                     password VARCHAR(255) NOT NULL,
                     registration_date DATETIME NOT NULL,
                     user_type ENUM('buyer', 'seller', 'admin') NOT NULL,
                     UNIQUE INDEX idx_email (email),
                     INDEX idx_user_type (user_type)
);

-- Categories table with self-reference for hierarchical categories
CREATE TABLE categories (
                          category_id INT AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(100) NOT NULL,
                          description TEXT,
                          parent_category_id INT,
                          FOREIGN KEY (parent_category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
                          INDEX idx_parent_category (parent_category_id)
);

-- Sellers table
CREATE TABLE sellers (
                       seller_id INT AUTO_INCREMENT PRIMARY KEY,
                       user_id INT NOT NULL UNIQUE,
                       company_name VARCHAR(255) NOT NULL,
                       contact_info TEXT,
                       rating DECIMAL(3,2) DEFAULT 0.00,
                       verification_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
                       FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                       INDEX idx_rating (rating)
);

-- Requests table
CREATE TABLE requests (
                        request_id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NOT NULL,
                        category_id INT,
                        creation_date DATETIME NOT NULL,
                        expiration_date DATETIME NOT NULL,
                        status ENUM('active', 'in_progress', 'completed', 'closed', 'expired') NOT NULL DEFAULT 'active',
                        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
                        INDEX idx_user (user_id),
                        INDEX idx_category (category_id),
                        INDEX idx_status (status),
                        INDEX idx_expiration (expiration_date)
);

-- Request details table
CREATE TABLE request_details (
                               detail_id INT AUTO_INCREMENT PRIMARY KEY,
                               request_id INT NOT NULL,
                               specification_type VARCHAR(100) NOT NULL,
                               specification_value TEXT NOT NULL,
                               FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
                               INDEX idx_request (request_id)
);

-- Bids table
CREATE TABLE bids (
                    bid_id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    seller_id INT NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    description TEXT NOT NULL,
                    delivery_time VARCHAR(100),
                    submission_date DATETIME NOT NULL,
                    status ENUM('active', 'accepted', 'rejected', 'withdrawn') NOT NULL DEFAULT 'active',
                    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
                    FOREIGN KEY (seller_id) REFERENCES sellers(seller_id) ON DELETE CASCADE,
                    UNIQUE INDEX idx_request_seller (request_id, seller_id),
                    INDEX idx_price (price),
                    INDEX idx_status (status)
);

-- Transactions table
CREATE TABLE transactions (
                            transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                            request_id INT NOT NULL,
                            bid_id INT NOT NULL,
                            user_id INT NOT NULL,
                            seller_id INT NOT NULL,
                            amount DECIMAL(10,2) NOT NULL,
                            payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
                            payment_date DATETIME,
                            FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
                            FOREIGN KEY (bid_id) REFERENCES bids(bid_id) ON DELETE CASCADE,
                            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                            FOREIGN KEY (seller_id) REFERENCES sellers(seller_id) ON DELETE CASCADE,
                            INDEX idx_payment_status (payment_status)
);

-- Reviews table
CREATE TABLE reviews (
                       review_id INT AUTO_INCREMENT PRIMARY KEY,
                       transaction_id INT NOT NULL,
                       rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                       comment TEXT,
                       review_date DATETIME NOT NULL,
                       reviewer_type ENUM('buyer', 'seller') NOT NULL,
                       FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
                       UNIQUE INDEX idx_transaction_reviewer (transaction_id, reviewer_type),
                       INDEX idx_rating (rating)
);

-- Messages table
CREATE TABLE messages (
                        message_id INT AUTO_INCREMENT PRIMARY KEY,
                        sender_id INT NOT NULL,
                        receiver_id INT NOT NULL,
                        request_id INT,
                        content TEXT NOT NULL,
                        timestamp DATETIME NOT NULL,
                        read_status BOOLEAN NOT NULL DEFAULT FALSE,
                        FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
                        FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
                        FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE SET NULL,
                        INDEX idx_sender (sender_id),
                        INDEX idx_receiver (receiver_id),
                        INDEX idx_request (request_id),
                        INDEX idx_timestamp (timestamp)
);

-- Insert some default categories
INSERT INTO categories (name, description, parent_category_id) VALUES
                                                                 ('Technology', 'Technology-related services and products', NULL),
                                                                 ('Design', 'Design services', NULL),
                                                                 ('Writing', 'Content writing and copywriting services', NULL),
                                                                 ('Business', 'Business services', NULL);

-- Insert child categories
INSERT INTO categories (name, description, parent_category_id) VALUES
                                                                 ('Web Development', 'Website development services', 1),
                                                                 ('Mobile Development', 'Mobile app development services', 1),
                                                                 ('Software Development', 'Custom software development', 1),
                                                                 ('Graphic Design', 'Visual design services', 2),
                                                                 ('UI/UX Design', 'User interface and experience design', 2),
                                                                 ('Logo Design', 'Brand and logo design services', 2),
                                                                 ('Content Writing', 'Articles, blog posts, and other content', 3),
                                                                 ('Copywriting', 'Marketing and advertising copy', 3),
                                                                 ('Technical Writing', 'Documentation and technical guides', 3),
                                                                 ('Marketing', 'Marketing and advertising services', 4),
                                                                 ('Consulting', 'Business consulting services', 4),
                                                                 ('Accounting', 'Financial and accounting services', 4);

-- Create an admin user
INSERT INTO users (username, email, password, registration_date, user_type) VALUES
  ('admin', 'admin@bidrequest.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'admin');
-- Note: The password hash above is for 'password'. In production, use a securely generated hash.
