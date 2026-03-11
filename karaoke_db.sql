-- 1. สร้างฐานข้อมูลและกำหนดให้รองรับภาษาไทย
CREATE DATABASE IF NOT EXISTS `karaoke_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `karaoke_db`;

-- 2. ตารางผู้ใช้งาน (ครอบคลุม เจ้าของร้าน, พนักงาน, ลูกค้า, บุคคลทั่วไปที่สมัครสมาชิก)
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('owner','employee','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- เพิ่มข้อมูลผู้ใช้งานตั้งต้น สำหรับทดสอบระบบ (รหัสผ่านคือ 1234)
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `phone`, `role`) VALUES
('owner_admin', '1234', 'สมชาย', 'ใจดี (เจ้าของร้าน)', 'owner@karaoke.com', '0801111111', 'owner'),
('emp_01', '1234', 'สมหญิง', 'บริการดี (พนักงาน)', 'emp01@karaoke.com', '0802222222', 'employee'),
('customer_01', '1234', 'ใจรัก', 'เสียงเพลง (ลูกค้า)', 'cust01@karaoke.com', '0803333333', 'customer');

-- 3. ตารางข้อมูลห้องคาราโอเกะ
CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL, -- ความจุคน
  `price_per_hour` decimal(10,2) NOT NULL, -- ราคาต่อชั่วโมง
  `room_image` varchar(255) DEFAULT 'default_room.jpg', -- รูปภาพห้อง
  `status` enum('available','maintenance') NOT NULL DEFAULT 'available', -- สถานะห้อง (ว่าง/ซ่อมบำรุง)
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ตารางโปรโมชั่น
CREATE TABLE `promotions` (
  `promo_id` int(11) NOT NULL AUTO_INCREMENT,
  `promo_name` varchar(100) NOT NULL,
  `discount_percent` int(11) DEFAULT 0, -- ส่วนลดแบบเปอร์เซ็นต์
  `discount_amount` decimal(10,2) DEFAULT 0.00, -- ส่วนลดแบบจำนวนเงิน
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`promo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. ตารางการจองห้องคาราโอเกะ (Booking)
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL, -- เชื่อมกับ users
  `room_id` int(11) NOT NULL, -- เชื่อมกับ rooms
  `promo_id` int(11) DEFAULT NULL, -- เชื่อมกับ promotions (ถ้ามี)
  `booking_date` date NOT NULL, -- วันที่เข้ามาใช้บริการ
  `start_time` time NOT NULL, -- เวลาเริ่ม
  `end_time` time NOT NULL, -- เวลาสิ้นสุด
  `total_hours` int(11) NOT NULL, -- จำนวนชั่วโมงรวม
  `room_price` decimal(10,2) NOT NULL, -- ค่าห้องก่อนหักส่วนลด
  `net_price` decimal(10,2) NOT NULL, -- ยอดรวมสุทธิหลังหักส่วนลด
  `booking_status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending', -- สถานะการจอง
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. ตารางข้อมูลการชำระเงิน
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('transfer','cash') NOT NULL,
  `slip_image` varchar(255) DEFAULT NULL, -- ไฟล์สลิปโอนเงิน
  `payment_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending', -- สถานะตรวจสอบสลิป
  `verified_by` int(11) DEFAULT NULL, -- พนักงาน/เจ้าของที่กดยืนยันสลิป
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. ตารางเมนูอาหารและเครื่องดื่ม
CREATE TABLE `menus` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` enum('food','drink','snack') NOT NULL,
  `menu_image` varchar(255) DEFAULT 'default_menu.jpg',
  `status` enum('available','out_of_stock') NOT NULL DEFAULT 'available',
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ตารางบิลการสั่งอาหาร (ผูกกับหมายเลขการจองห้อง)
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL, -- สั่งให้ออเดอร์นี้เข้าไปที่ห้องไหน
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_status` enum('pending','preparing','served','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. ตารางรายละเอียดรายการอาหารในบิล (Order Items)
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL, -- เชื่อมกับ orders
  `menu_id` int(11) NOT NULL, -- เชื่อมกับ menus
  `quantity` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_id`) REFERENCES `menus`(`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;