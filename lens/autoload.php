<?php

namespace Lens_0_0_56\Lens;

call_user_func(function() {
	$project = dirname(__DIR__);
	$namespaces = array(
		'Lens_0_0_56' => 'src'
	);

	require "{$project}/src/Lens/Autoloader.php";

	new Autoloader($project, $namespaces);
});
