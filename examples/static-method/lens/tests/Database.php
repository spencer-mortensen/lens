<?php

namespace Example;

use PDO;

// Test
$database = new Database();
$database->getDrivers();

// Effect
PDO::getAvailableDrivers(); // return [];

// Effect
PDO::getAvailableDrivers(); // return ['mysql'];

// Effect
PDO::getAvailableDrivers(); // return ['mysql', 'sqlite'];
