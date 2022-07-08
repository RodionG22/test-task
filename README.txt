# test-task
1. Запустить на локальном или захостить сайт.

2. Создать бд в phpmyadmin. Создать таблицу Calendar 

  SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
  START TRANSACTION;
  SET time_zone = "+00:00";

  CREATE TABLE `calendar` (
   `id` int NOT NULL,
   `datetime` datetime NOT NULL,
   `duration` int NOT NULL,
   `title` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
   `description` varchar(20) COLLATE utf8mb4_general_ci NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


  ALTER TABLE `calendar`
    ADD PRIMARY KEY (`id`);

  ALTER TABLE `calendar`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;
  COMMIT;
  
  
3.В папке configh.php заполнить hostname,username,password,db_database и bearer_token

4. Для запросов можно использовать Insomnia и альтернативные программы. 
 
5. REQUEST_METHOD:
GET - //http://{domen}/?calendar
POST - //http://{domen}/?calendar
PATCH - //http://{domen}/?calendar/<id>
DELETE - //http://{domen}/?calendar/<id>

