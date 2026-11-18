CREATE DATABASE IF NOT EXISTS online_examination_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,INDEX,ALTER,REFERENCES ON online_examination_system.* TO 'root'@'localhost';
