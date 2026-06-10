-- LIVVRA E-Commerce Database Setup for PostgreSQL

CREATE TABLE admins (
  id SERIAL PRIMARY KEY,
  username varchar(100) NOT NULL,
  password_hash varchar(255) NOT NULL,
  email varchar(255) DEFAULT NULL,
  is_active integer DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (username, password_hash, email, is_active) VALUES
('OfficialLivvra', '$2y$10$JIlG81bpxxgR9yclRtdS8.LJEyVZp9BJdeGLDyUii8tl1wob/uxfO', 'admin@livvra.com', 1),
('admin', '$2y$10$hyZfio7CZQT9l9VmTLF8qOqt3eMvYdhO/5EO0NMBMqluG3oJNjkFK', NULL, 1);

CREATE TABLE categories (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  description text DEFAULT NULL,
  icon_class varchar(100) DEFAULT NULL,
  is_active integer DEFAULT 1,
  sort_order integer DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories (name, slug, description, icon_class, is_active, sort_order) VALUES
('Skin Care', 'skin-care', 'Premium skincare products', 'fa-leaf', 1, 1),
('Gym Food & Fitness', 'gym-food-fitness', 'Fitness and nutrition products', 'fa-dumbbell', 1, 2),
('Blood Sugar & Chronic Care', 'blood-sugar-chronic-care', 'Health and wellness products', 'fa-heart', 1, 3);

CREATE TABLE hero_slides (
  id SERIAL PRIMARY KEY,
  badge varchar(255) DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  subtitle text DEFAULT NULL,
  price varchar(100) DEFAULT NULL,
  discount varchar(100) DEFAULT NULL,
  image varchar(255) DEFAULT NULL,
  video_url text DEFAULT NULL,
  button_text varchar(100) DEFAULT NULL,
  button_link varchar(255) DEFAULT NULL,
  display_order integer DEFAULT 0,
  is_active integer DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
  id SERIAL PRIMARY KEY,
  order_number varchar(50) NOT NULL,
  customer_name varchar(255) DEFAULT NULL,
  customer_email varchar(255) DEFAULT NULL,
  customer_phone varchar(20) DEFAULT NULL,
  shipping_address text DEFAULT NULL,
  city varchar(100) DEFAULT NULL,
  state varchar(100) DEFAULT NULL,
  pincode varchar(20) DEFAULT NULL,
  subtotal decimal(10,2) DEFAULT NULL,
  discount_amount decimal(10,2) DEFAULT 0.00,
  final_amount decimal(10,2) DEFAULT NULL,
  promo_code varchar(50) DEFAULT NULL,
  shipping_fee decimal(10,2) DEFAULT NULL,
  total_amount decimal(10,2) DEFAULT NULL,
  payment_method varchar(50) DEFAULT 'cod',
  payment_status varchar(50) DEFAULT 'pending',
  order_status varchar(50) DEFAULT 'processing',
  notes text DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
  id SERIAL PRIMARY KEY,
  order_id integer NOT NULL,
  product_id integer DEFAULT NULL,
  product_name varchar(255) DEFAULT NULL,
  product_image varchar(255) DEFAULT NULL,
  price decimal(10,2) DEFAULT NULL,
  unit_price decimal(10,2) DEFAULT NULL,
  quantity integer DEFAULT 1,
  total decimal(10,2) DEFAULT NULL,
  total_price decimal(10,2) DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pincodes (
  id SERIAL PRIMARY KEY,
  pincode varchar(10) NOT NULL,
  city varchar(100) DEFAULT NULL,
  is_active integer DEFAULT 1
);

CREATE TABLE products (
  id SERIAL PRIMARY KEY,
  category_id integer DEFAULT NULL,
  name varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  sku varchar(100) DEFAULT NULL,
  price decimal(10,2) NOT NULL,
  mrp decimal(10,2) DEFAULT NULL,
  short_description text DEFAULT NULL,
  long_description text DEFAULT NULL,
  benefits text DEFAULT NULL,
  image varchar(255) DEFAULT NULL,
  stock_qty integer DEFAULT 100,
  rating decimal(3,2) DEFAULT 5.00,
  reviews_count integer DEFAULT 0,
  is_featured integer DEFAULT 0,
  is_active integer DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (category_id, name, slug, sku, price, mrp, short_description, long_description, benefits, image, stock_qty, is_featured, is_active) VALUES
(1, 'LIVVRA Kumkumadi Beauty Oil', 'livvra-kumkumadi-beauty-oil', 'KUMKUMADI-001', 599.00, 799.00, 'Premium Ayurvedic elixir for radiant, youthful skin', 'Legendary elixir', 'Enhanced glow', 'kumkumadi-1.png', 100, 1, 1),
(2, 'LIVVRA Dy-B-Fuel RAS', 'livvra-dy-b-fuel-ras', 'DY-B-FUEL-001', 655.00, 899.00, 'Ayurvedic remedy for healthy blood sugar balance', 'Holistic solution', 'Sugar balance', 'dy-b-fuel-1.png', 100, 1, 1),
(3, 'LIVVRA Yeast Protein', 'livvra-yeast-protein', 'YEAST-PROTEIN-001', 799.00, 1099.00, 'Premium plant-based protein for strength and wellness', 'Fermented solution', 'Complete protein', 'yeast-oil-1.png', 100, 1, 1);

CREATE TABLE settings (
  id SERIAL PRIMARY KEY,
  key varchar(100) NOT NULL UNIQUE,
  value text,
  description varchar(255) DEFAULT NULL
);

INSERT INTO settings (key, value, description) VALUES 
('site_name', 'LIVVRA', 'Website Name'),
('reward_redeem_rate', '1', '1 Coin = X Rupees');
