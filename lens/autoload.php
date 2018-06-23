<?php

namespace _Lens\SpencerMortensen\Autoloader;

call_user_func(function() {
	$project = dirname(__DIR__);
	$namespaces = [
		'_Lens' => 'src'
	];

	require_once "{$project}/src/SpencerMortensen/Autoloader/Autoloader.php";

	new Autoloader($project, $namespaces);
});
