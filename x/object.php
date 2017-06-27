<?php

define('TESTPHP_TESTS_DIRECTORY', '/home/smortensen/Projects/github.com/smortensen/cinnabari/testphp');

spl_autoload_register(
	function ($class) {
		$namespacePrefix = 'TestPhp\\';
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

require TESTPHP_TESTS_DIRECTORY . '/autoload.php';

use Datto\Cinnabari\Resolver\Tokens\ObjectToken;
use TestPhp\Archivist\Archivist;

$properties = array(
	'x' => 1
);

$dataType = array(6, 666);

$objectToken = new ObjectToken($properties, $dataType);

$archive = Archivist::archive($objectToken);

var_dump($archive);
