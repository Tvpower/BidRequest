-- BidRequest Database Schema Update for Products and Services

-- First, alter the requests table to add a type field
ALTER TABLE requests 
ADD COLUMN type ENUM('service', 'product') NOT NULL DEFAULT 'service' AFTER category_id,
ADD COLUMN budget DECIMAL(10,2) DEFAULT NULL AFTER type,
ADD COLUMN desired_condition ENUM('new', 'like-new', 'good', 'fair', 'poor', 'any') DEFAULT NULL AFTER budget;

-- Alter the bids table to add product-specific fields
ALTER TABLE bids
ADD COLUMN product_condition ENUM('new', 'like-new', 'good', 'fair', 'poor') DEFAULT NULL AFTER description,
ADD COLUMN product_brand VARCHAR(100) DEFAULT NULL AFTER product_condition,
ADD COLUMN product_model VARCHAR(100) DEFAULT NULL AFTER product_brand;

-- Create a table for bid images (for product bids)
CREATE TABLE bid_images (
  image_id INT AUTO_INCREMENT PRIMARY KEY,
  bid_id INT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  is_primary BOOLEAN NOT NULL DEFAULT FALSE,
  upload_date DATETIME NOT NULL,
  FOREIGN KEY (bid_id) REFERENCES bids(bid_id) ON DELETE CASCADE,
  INDEX idx_bid (bid_id)
);

-- Update the status enum in the requests table to include product-specific statuses
ALTER TABLE requests 
MODIFY COLUMN status ENUM('active', 'in_progress', 'completed', 'closed', 'expired') NOT NULL DEFAULT 'active';

-- Update the status enum in the bids table to include product-specific statuses
ALTER TABLE bids
MODIFY COLUMN status ENUM('active', 'accepted', 'rejected', 'withdrawn', 'sold', 'shipped', 'delivered') NOT NULL DEFAULT 'active';

-- Add shipping information to the transactions table
ALTER TABLE transactions
ADD COLUMN shipping_address TEXT AFTER payment_date,
ADD COLUMN tracking_number VARCHAR(100) AFTER shipping_address,
ADD COLUMN delivery_status ENUM('not_applicable', 'pending', 'shipped', 'delivered') NOT NULL DEFAULT 'not_applicable' AFTER tracking_number;

-- Add product-specific fields to the request_details table
ALTER TABLE request_details
ADD COLUMN is_required BOOLEAN NOT NULL DEFAULT TRUE AFTER specification_value;
  INDEX idx_rating (rating)
);

-- Add some product-specific categories
INSERT INTO categories (name, description, parent_category_id) VALUES
('Electronics', 'Electronic devices and accessories', NULL),
('Home & Garden', 'Items for home and garden', NULL),
('Clothing', 'Apparel and fashion items', NULL),
('Collectibles', 'Collectible items and memorabilia', NULL);

-- Add child categories for products
INSERT INTO categories (name, description, parent_category_id) VALUES
('Computers', 'Desktop and laptop computers', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Electronics')),
('Smartphones', 'Mobile phones and accessories', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Electronics')),
('Audio Equipment', 'Speakers, headphones, and audio devices', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Electronics')),
('Furniture', 'Home and office furniture', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Home & Garden')),
('Kitchen', 'Kitchen appliances and accessories', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Home & Garden')),
('Garden Tools', 'Tools and equipment for gardening', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Home & Garden')),
('Men\'s Clothing', 'Clothing items for men', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Clothing')),
('Women\'s Clothing', 'Clothing items for women', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Clothing')),
('Jewelry', 'Jewelry and accessories', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Clothing')),
('Coins', 'Collectible coins and currency', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Collectibles')),
('Trading Cards', 'Collectible trading cards', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Collectibles')),
('Vintage Items', 'Vintage and antique collectibles', (SELECT category_id FROM (SELECT * FROM categories) AS temp WHERE name = 'Collectibles'));

-- Add example product specifications for request details
INSERT INTO request_details (request_id, specification_type, specification_value, is_required) VALUES
(1, 'Brand', 'Any', TRUE),
(1, 'Model', 'iPhone 15 or equivalent', TRUE),
(1, 'Storage', '128GB minimum', TRUE),
(1, 'Color', 'Black preferred', FALSE);
