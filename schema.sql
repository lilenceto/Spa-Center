USE spa_center;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- в минути
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status ENUM('pending','confirmed','canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    hired_at DATE
);
INSERT INTO users (name, email, password, phone) VALUES
('Ivan Ivanov', 'ivan@mail.com', 'test123', '0888123456'),
('Maria Petrova', 'maria@mail.com', 'test456', '0888123457');
INSERT INTO services (name, description, duration, price, category) VALUES
('Класически масаж', 'Релаксиращ масаж на цяло тяло', 60, 60.00, 'масаж'),
('Йога', 'Групова йога тренировка', 45, 15.00, 'фитнес');

CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hired_at` date DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_employee_category` (`category_id`),
  CONSTRAINT `fk_employee_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'Иван Иванов','male','масажист','0888000001','ivan@example.com',NULL,1),(2,'Станимир Костов','male','масажист','0888000002','stanimir@example.com',NULL,1),(3,'Димитър Йорданов','male','масажист','0888000003','dimitur@example.com',NULL,1),(4,'Петър Стоянов','male','масажист','0888000004','petur@example.com',NULL,1),(5,'Николай Данчев','male','масажист','0888000005','nikolay@example.com',NULL,1),(6,'Александра Петрова','female','масажист','0888000010','aleksandra@example.com',NULL,1),(7,'Виктория Христова','female','масажист','0888000011','viktoria@example.com',NULL,1),(8,'Гергана Христова','female','масажист','0888000012','gergana@example.com',NULL,1),(9,'Елиза Стоянова','female','масажист','0888000013','eliza@example.com',NULL,1),(10,'Мария Петрова','female','козметик','0888000020','maria@example.com',NULL,3),(11,'Симона Георгиева','female','козметик','0888000021','simona@example.com',NULL,3),(12,'Наталия Иванова','female','козметик','0888000022','natalia@example.com',NULL,3),(13,'Лилия Симеонова','female','козметик','0888000023','lilia@example.com',NULL,3),(14,'Деница Петрова','female','козметик','0888000024','denica@example.com',NULL,3),(15,'Георги Георгиев','male','фитнес инструктор','0888000030','georgi.fitness@example.com',NULL,2),(16,'Ива Иванова','female','фитнес инструктор','0888000031','iva.fitness@example.com',NULL,2),(17,'Стефка Димитрова','female','СПА терапевт','0888000040','stefka.spa@example.com',NULL,4),(18,'Елена Николова','female','СПА терапевт','0888000041','elena.spa@example.com',NULL,4);
