-- LIVVRA MySQL Database Dump
-- Generated for Hostinger shared hosting compatibility

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `icon_class` varchar(50) DEFAULT 'fa-leaf',
  `icon_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `short_description` text,
  `long_description` text,
  `benefits` text,
  `features` text,
  `price` decimal(10,2) NOT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 4.50,
  `reviews_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hero slides
CREATE TABLE IF NOT EXISTS `hero_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `badge` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text,
  `price` decimal(10,2) DEFAULT NULL,
  `discount` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `button_text` varchar(50) DEFAULT 'SHOP NOW',
  `button_link` varchar(255) DEFAULT 'products.php',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Data
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon_class`) VALUES
(1, 'Skin Care', 'skin-care', 'Pure Ayurvedic skin care products', 'fa-spa'),
(2, 'Gym Food & Fitness', 'gym-food-fitness', 'Natural supplements for fitness', 'fa-dumbbell'),
(3, 'Chronic Care', 'blood-sugar-chronic-care', 'Herbal remedies for chronic health', 'fa-heartbeat');

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `price`, `mrp`, `image`, `is_featured`) VALUES
(1, 1, 'Kumkumadi Beauty Oil', 'kumkumadi-beauty-oil', 1999.00, 2499.00, 'kumkumadi-1.png', 1),
(2, 3, 'Dy-B-Fuel RAS', 'dy-b-fuel-ras', 1199.00, 1599.00, 'uploads/products/dy-b-fuel-main.jpg', 1),
(3, 2, 'Yeast Protein', 'yeast-protein', 799.00, 999.00, 'yeast-oil-1.png', 1);

INSERT INTO `hero_slides` (`id`, `badge`, `title`, `subtitle`, `price`, `image`, `display_order`) VALUES
(1, 'New Arrival', 'Kumkumadi Beauty Oil', 'Legendary Ayurvedic elixir for radiant skin', 1999.00, 'assets/images/banners/hero-kumkumadi.png', 1),
(2, 'Bestseller', 'Dy-B-Fuel RAS', 'Natural blood sugar management', 1199.00, 'assets/images/banners/hero-section.png', 2);

COMMIT;
