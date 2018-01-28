<?php

namespace SpencerMortensen\Autoloader;

call_user_func(function() {
	$project = dirname(__DIR__);

	require "{$project}/testing/namespaces.php";
	require "{$project}/vendor/spencer-mortensen/autoloader/src/Autoloader.php";

	new Autoloader($project, $namespaces);
});
