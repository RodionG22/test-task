<?php /* Задача Календарь

* Написать Rest API для ведения личного календаря. В качестве аутентификации использовать Bearer-токены. Доступные эндпойнты:

GET /calendar
Возвращает все записи в интервале между параметрами date_start и date_end. Доступен только для аутентифицированных пользователей.

POST /calendar
Добавление новой записи в календарь. Запись добавляется на конкретное время (date) и с конкретной длительностью в минутах (duration).
Также у записи есть поля title (обязательное) и description (не обязательное).
Если запись добавляет аутентифицированный пользователь, то возможно пересечение различных записей по времени (например, в календаре уже есть запись с 14:00 до 15:30, а пользователь добавляет новую запись с 15:00 до 16:00).
Анонимный пользователь также может добавлять новые записи, но только на полностью свободное от других событий время.

PATCH /calendar/<id>
Обновление конкретной записи в календаре. Обновлять можно любые поля, но только у записей, до начала которых больше трех часов. Доступен только для аутентифицированных пользователей.

DELETE /calendar/<id>
Удаление конкретной записи в календаре. Удалять можно только записи до которых осталось больше 3 часов. Доступен только для аутентифицированных пользователей.

Удачи!

С уважением, команда Egolist


Создание таблицы
 
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





*/

// DB 
require("config.php");


function autoloader($class){

    require_once 'Classes/' . $class . '.php';
}

spl_autoload_register('autoloader');

// таблица calendar 



$api=new Calendar();
$api->endpointsApi();

//http://{domen}/?calendar/<id>


?>