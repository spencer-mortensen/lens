<?php

namespace Example;

require __DIR__ . '/vendor/autoload.php';

$database = new Database();
$drivers = $database->getDrivers();

echo "drivers: ", var_export($drivers, true), "\n";
