<?php

spl_autoload_register(
	function ($class)
	{
		$namespace = 'TestPhp';
		$libraryDirectory = dirname(__DIR__) . '/src';

		$namespacePrefix = $namespace . '\\';
		$namespacePrefixLength = strlen($namespacePrefix);

		if (strncmp($class, $namespacePrefix, $namespacePrefixLength) !== 0) {
			return;
		}

		$relativeClassName = substr($class, $namespacePrefixLength);
		$relativeFilePath = strtr($relativeClassName, '\\', '/') . '.php';
		$absoluteFilePath = "{$libraryDirectory}/{$relativeFilePath}";

		if (is_file($absoluteFilePath)) {
			include $absoluteFilePath;
		}
	}
);
