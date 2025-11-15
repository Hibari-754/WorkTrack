-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 15, 2025 lúc 10:17 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `simple_todo_app`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL COMMENT 'PRIMARY KEY [cite: 18]',
  `user_id` int(11) NOT NULL COMMENT 'FOREIGN KEY liên kết với users.id [cite: 19]',
  `title` varchar(255) NOT NULL COMMENT 'Tên công việc [cite: 21]',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết, có thể NULL [cite: 22]',
  `due_date` date DEFAULT NULL COMMENT 'Ngày hết hạn, có thể NULL [cite: 23]',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái: pending, in_progress, completed [cite: 24]',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo [cite: 25]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `status`, `created_at`) VALUES
(3, 1, 'thiết kế', 'adsa', '2025-11-15', 'pending', '2025-11-13 09:32:52'),
(5, 3, 'thiết kế', 'web', '2025-11-13', 'in_progress', '2025-11-13 13:11:48'),
(10, 5, 'thiết kế', NULL, '2025-11-16', 'in_progress', '2025-11-14 03:22:58'),
(11, 5, 'code', NULL, '2025-11-16', 'pending', '2025-11-14 07:30:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'PRIMARY KEY',
  `username` varchar(50) NOT NULL COMMENT 'Tên đăng nhập, UNIQUE [cite: 12]',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu đã băm (HASHED) [cite: 13]',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email, UNIQUE, có thể NULL [cite: 14]',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Thời gian tạo [cite: 15]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'anhquan123', '$2y$10$fOnldGyWeuQOswxy6YZj.OljUMEHswrzTUHAP7VTRZTLFAtFBv.xG', NULL, '2025-11-12 14:09:13'),
(2, 'anhduc', '$2y$10$XJHu6mKP.F7sGzAo/GnLu.0aaJTjDnO30YK43j0fsWT5ZI9ZYQiz.', NULL, '2025-11-13 10:01:12'),
(3, 'thanhan', '$2y$10$WhTKZMRADWpRrmjRse6V1u7DAceB3GiTfvJZfYcFDOkIVOV8PT6EK', NULL, '2025-11-13 10:10:36'),
(4, 'thanhtuan', '$2y$10$XkZP5j8idyMQJWGulUp5A.X3xDFm9sABr0rG6OEc5HnxLiP1kE0Eu', NULL, '2025-11-13 10:13:15'),
(5, 'xoài', '$2y$10$uFa1fxRYo48QYWAjzFU55Oo.cYO/Jbl55lY8KYsd4wx.jYsRdZggS', NULL, '2025-11-13 15:45:14');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PRIMARY KEY [cite: 18]', AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PRIMARY KEY', AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
