<?php

namespace Lens;

call_user_func(function() {
	$project = dirname(__DIR__);

	require "{$project}/lens/namespaces.php";
	require "{$project}/src/Autoloader.php";

	new Autoloader($project, $namespaces);
});
