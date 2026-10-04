-- Robot Collection Web App
-- Database: robot_collection

CREATE DATABASE IF NOT EXISTS robot_collection
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE robot_collection;

-- ---------- TABLES ----------

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT
);

CREATE TABLE robots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  manufacturer VARCHAR(150),
  country VARCHAR(100),
  year_introduced YEAR,
  description TEXT,
  specs TEXT,
  image VARCHAR(255),
  video_url VARCHAR(255),   -- YouTube embed link
  model_url VARCHAR(255),   -- .glb path/URL; NULL = "3D model unavailable"
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE bookmarks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  robot_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_bookmark (user_id, robot_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (robot_id) REFERENCES robots(id) ON DELETE CASCADE
);

-- ---------- SAMPLE DATA ----------

-- Admin login: admin@robots.com / admin123  (change after first login)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@robots.com', '$2y$10$z6yIsQmhU7ZmyrWh9.O18el7fNVGju/uLGClhyi2/yZjPzq2t2axi', 'admin');

INSERT INTO categories (name, description) VALUES
('Industrial', 'Robots used in manufacturing, assembly and welding.'),
('Humanoid', 'Human-shaped robots designed for interaction and mobility research.'),
('Mobile / Wheeled', 'Ground robots for navigation, delivery and exploration.'),
('Drone', 'Unmanned aerial vehicles.'),
('Medical', 'Robots assisting surgery and healthcare.'),
('Space', 'Robots built for space exploration.'),
('Educational', 'Robots for learning and research.');

INSERT INTO robots (category_id, name, manufacturer, country, year_introduced, description, specs, image, video_url, model_url) VALUES
(2, 'Atlas', 'Boston Dynamics', 'USA', 2013,
 'Bipedal humanoid robot known for parkour, jumping and dynamic balance.',
 'Height: ~1.5 m; Actuation: Hydraulic (older) / Electric (new)',
 'assets/images/atlas.jpg', NULL, NULL),
(3, 'Spot', 'Boston Dynamics', 'USA', 2019,
 'Quadruped robot used for inspection, mapping and autonomous patrol.',
 'Weight: ~32 kg; Payload: ~14 kg; Sensors: cameras, LiDAR (optional)',
 'assets/images/spot.jpg', NULL, NULL),
(1, 'UR5e', 'Universal Robots', 'Denmark', 2018,
 'Collaborative robot arm used in assembly, packaging and lab automation.',
 'DOF: 6; Payload: 5 kg; Reach: 850 mm',
 'assets/images/ur5e.jpg', NULL, NULL),
(5, 'da Vinci Surgical System', 'Intuitive Surgical', 'USA', 2000,
 'Robot-assisted system for minimally invasive surgery.',
 'Multi-arm console-controlled system with 3D vision',
 'assets/images/davinci.jpg', NULL, NULL),
(6, 'Perseverance Rover', 'NASA JPL', 'USA', 2021,
 'Mars rover searching for signs of ancient life and collecting samples.',
 'Mass: ~1025 kg; Power: MMRTG; Wheels: 6',
 'assets/images/perseverance.jpg', NULL, NULL),
(7, 'TurtleBot3', 'ROBOTIS', 'South Korea', 2017,
 'Small ROS-based mobile robot widely used in education and research.',
 'ROS/ROS2 support; LiDAR; Burger/Waffle variants',
 'assets/images/turtlebot3.jpg', NULL, NULL);
