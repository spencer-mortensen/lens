<?php

call_user_func(function () {
	$projectDirectory = dirname(__DIR__);

	$classes = array(
		'Lens' => "{$projectDirectory}/src",
		'SpencerMortensen\\ParallelProcessor' => "{$projectDirectory}/vendor/spencer-mortensen/parallel-processor/src",
		'SpencerMortensen\\Parser' => "{$projectDirectory}/vendor/spencer-mortensen/parser/src"
	);

	foreach ($classes as $namespacePrefix => $libraryPath) {
		$namespacePrefix .= '\\';
		$namespacePrefixLength = strlen($namespacePrefix);

		$autoloader = function ($class) use ($namespacePrefix, $namespacePrefixLength, $libraryPath) {
			if (strncmp($class, $namespacePrefix, $namespacePrefixLength) !== 0) {
				return;
			}

			$relativeClassName = substr($class, $namespacePrefixLength);
			$relativeFilePath = strtr($relativeClassName, '\\', '/') . '.php';
			$absoluteFilePath = "{$libraryPath}/{$relativeFilePath}";

			if (is_file($absoluteFilePath)) {
				include $absoluteFilePath;
			}
		};

		spl_autoload_register($autoloader);
	}
});
