<?php

call_user_func(function () {
	$projectDirectory = dirname(__DIR__);
	$vendorDirectory = "{$projectDirectory}/vendor";

	$classes = array(
		'Lens' => "{$projectDirectory}/src",
		'Monolog' => "{$vendorDirectory}/monolog/monolog/src/Monolog",
		'Psr\\Log' => "{$vendorDirectory}/psr/log/Psr/Log",
		'SpencerMortensen\\Exceptions' => "{$vendorDirectory}/spencer-mortensen/exceptions/src",
		'SpencerMortensen\\Html5' => "{$vendorDirectory}/spencer-mortensen/html5/src",
		'SpencerMortensen\\ParallelProcessor' => "{$vendorDirectory}/spencer-mortensen/parallel-processor/src",
		'SpencerMortensen\\Parser' => "{$vendorDirectory}/spencer-mortensen/parser/src",
		'SpencerMortensen\\Paths' => "{$vendorDirectory}/spencer-mortensen/paths/src",
		'SpencerMortensen\\RegularExpressions' => "{$vendorDirectory}/spencer-mortensen/regular-expressions/src"
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
