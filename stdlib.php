<?php
spl_autoload_register(function ($class) {
    if (file_exists("/var/www/html/flight_api/database/$class.php")) { // Database
        include "/var/www/html/flight_api/database/$class.php";
    } elseif (file_exists("/var/www/html/flight_api/utilities/$class.php")) { // Utilities
        include "/var/www/html/flight_api/utilities/$class.php";
    } elseif (file_exists("/var/www/html/flight_api/routers/$class.php")) { // Routers
        include "/var/www/html/flight_api/routers/$class.php";
    }
});