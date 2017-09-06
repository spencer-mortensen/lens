<?php

spl_autoload_register(
	function ($class) {
		$namespacePrefix = 'Lens\\';
		$namespacePrefixLength = strlen($namespacePrefix);

		if (strncmp($class, $namespacePrefix, $namespacePrefixLength) !== 0) {
			return;
		}

		$relativeClassName = substr($class, $namespacePrefixLength);
		$filePath = dirname(__DIR__) . '/src/' . strtr($relativeClassName, '\\', '/') . '.php';

		if (is_file($filePath)) {
			include $filePath;
		}
	}
);
