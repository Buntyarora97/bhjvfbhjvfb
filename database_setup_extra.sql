-- Additional tables required by the application

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL UNIQUE,
  phone varchar(20) DEFAULT '',
  password varchar(255) NOT NULL,
  address text DEFAULT '',
  pincode varchar(20) DEFAULT '',
  city varchar(100) DEFAULT '',
  state varchar(100) DEFAULT '',
  reward_coins integer DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_inquiries (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  email varchar(255) DEFAULT NULL,
  phone varchar(20) DEFAULT '',
  subject varchar(255) DEFAULT '',
  message text,
  status varchar(50) DEFAULT 'new',
  handled_by integer DEFAULT NULL,
  handled_at timestamp DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_images (
  id SERIAL PRIMARY KEY,
  product_id integer NOT NULL,
  image_path varchar(500) NOT NULL,
  is_primary integer DEFAULT 0,
  display_order integer DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reviews (
  id SERIAL PRIMARY KEY,
  product_id integer DEFAULT NULL,
  name varchar(255) DEFAULT 'Customer',
  rating integer DEFAULT 5,
  comment text,
  is_active integer DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stories (
  id SERIAL PRIMARY KEY,
  name varchar(255) DEFAULT NULL,
  story_text text,
  rating integer DEFAULT 5,
  image_path varchar(500) DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS promo_codes (
  id SERIAL PRIMARY KEY,
  code varchar(100) NOT NULL UNIQUE,
  discount_type varchar(20) DEFAULT 'percent',
  discount_value decimal(10,2) DEFAULT 0,
  min_order_amount decimal(10,2) DEFAULT 0,
  max_discount decimal(10,2) DEFAULT NULL,
  usage_limit integer DEFAULT NULL,
  used_count integer DEFAULT 0,
  expires_at timestamp DEFAULT NULL,
  is_active integer DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS video_popups (
  id SERIAL PRIMARY KEY,
  video_url varchar(500) DEFAULT NULL,
  buy_link varchar(500) DEFAULT NULL,
  view_count integer DEFAULT 0,
  is_active integer DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
);
