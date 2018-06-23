<?php

namespace Lens_0_0_57\SpencerMortensen\Autoloader;

call_user_func(function() {
	$project = dirname(__DIR__);
	$namespaces = [
		'Lens_0_0_57' => 'src'
	];

	require_once "{$project}/src/SpencerMortensen/Autoloader/Autoloader.php";

	new Autoloader($project, $namespaces);
});
