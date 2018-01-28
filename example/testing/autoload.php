<?php

namespace SpencerMortensen\Autoloader;

$project = dirname(dirname(__DIR__));

$classes = array(
	'Example' => 'example/src'
);

require "{$project}/vendor/spencer-mortensen/autoloader/src/Autoloader.php";

new Autoloader($project, $classes);
