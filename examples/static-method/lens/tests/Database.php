<?php

namespace Example;

use PDO;

// Test
$database = new Database();
$drivers = $database->getDrivers();

// Effect
$drivers = PDO::getAvailableDrivers(); // return [];

// Effect
$drivers = PDO::getAvailableDrivers(); // return ['mysql'];

// Effect
$drivers = PDO::getAvailableDrivers(); // return ['mysql', 'sqlite'];
