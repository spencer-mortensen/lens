<?php

(function () {
	$classes = array(
		'Lens' => dirname(__DIR__) . '/src',
		'SpencerMortensen\\ParallelProcessor' => dirname(__DIR__) . '/vendor/spencer-mortensen/parallel-processor/src'
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
})();
