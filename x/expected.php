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

spl_autoload_register(
	function ($path)
	{
		$mockPrefix = 'TestPhp\\Mock\\';
		$mockPrefixLength = strlen($mockPrefix);

		if (strncmp($path, $mockPrefix, $mockPrefixLength) !== 0) {
			return;
		}

		$parentClass = substr($path, $mockPrefixLength);

		$mockBuilder = new \TestPhp\MockBuilder($mockPrefix, $parentClass);
		$mockCode = $mockBuilder->getMock();

		eval($mockCode);
	}
);

require TESTPHP_TESTS_DIRECTORY . '/autoload.php';

use Datto\Cinnabari\Exception;
use Datto\Cinnabari\Language\Types;
use Datto\Cinnabari\Parser\Tokens\ParameterToken as ParserParameterToken;
use Datto\Cinnabari\Parser\Tokens\PropertyToken as ParserPropertyToken;
use TestPhp\Mock\Datto\Cinnabari\Language\Functions;
use TestPhp\Mock\Datto\Cinnabari\Language\Properties;
use Datto\Cinnabari\Resolver;
use Datto\Cinnabari\Resolver\Request;
use Datto\Cinnabari\Resolver\Tokens\PropertyToken as ResolverPropertyToken;

$functions = new Functions();
$properties = new Properties(array());

$input = new ParserPropertyToken(array('unknown'));

\TestPhp\Agent::record(array($properties, 'getType'), array('Database', 'unknown'), array(1, Exception::unknownProperty('Database', 'unknown')));
