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

\TestPhp\Agent::setScript('a:3:{i:0;a:3:{i:0;a:2:{i:0;O:39:"TestPhp\\Packager\\Packages\\ObjectPackage":3:{s:43:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'id";s:32:"00000000418c5959000000002a5bd7af";s:45:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'data";s:59:"O:47:"TestPhp\\Mock\\Datto\\Cinnabari\\Language\\Functions":0:{}";s:46:"' . "\0" . 'TestPhp\\Packager\\Packages\\Package' . "\0" . 'packageType";i:1;}i:1;s:11:"__construct";}i:1;a:0:{}i:2;a:2:{i:0;i:0;i:1;N;}}i:1;a:3:{i:0;a:2:{i:0;O:39:"TestPhp\\Packager\\Packages\\ObjectPackage":3:{s:43:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'id";s:32:"00000000418c5956000000002a5bd7af";s:45:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'data";s:117:"O:48:"TestPhp\\Mock\\Datto\\Cinnabari\\Language\\Properties":1:{s:47:"' . "\0" . 'Datto\\Cinnabari\\Language\\Properties' . "\0" . 'properties";N;}";s:46:"' . "\0" . 'TestPhp\\Packager\\Packages\\Package' . "\0" . 'packageType";i:1;}i:1;s:11:"__construct";}i:1;a:1:{i:0;a:0:{}}i:2;a:2:{i:0;i:0;i:1;N;}}i:2;a:3:{i:0;a:2:{i:0;O:39:"TestPhp\\Packager\\Packages\\ObjectPackage":3:{s:43:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'id";s:32:"00000000418c5956000000002a5bd7af";s:45:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'data";s:117:"O:48:"TestPhp\\Mock\\Datto\\Cinnabari\\Language\\Properties":1:{s:47:"' . "\0" . 'Datto\\Cinnabari\\Language\\Properties' . "\0" . 'properties";N;}";s:46:"' . "\0" . 'TestPhp\\Packager\\Packages\\Package' . "\0" . 'packageType";i:1;}i:1;s:7:"getType";}i:1;a:2:{i:0;s:8:"Database";i:1;s:7:"unknown";}i:2;a:2:{i:0;i:1;i:1;O:39:"TestPhp\\Packager\\Packages\\ObjectPackage":3:{s:43:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'id";s:32:"00000000418c5954000000002a5bd7af";s:45:"' . "\0" . 'TestPhp\\Packager\\Packages\\ObjectPackage' . "\0" . 'data";s:3283:"O:25:"Datto\\Cinnabari\\Exception":8:{s:31:"' . "\0" . 'Datto\\Cinnabari\\Exception' . "\0" . 'data";a:2:{s:5:"class";s:8:"Database";s:8:"property";s:7:"unknown";}s:10:"' . "\0" . '*' . "\0" . 'message";s:47:"The "Database" class has no "unknown" property.";s:17:"' . "\0" . 'Exception' . "\0" . 'string";s:0:"";s:7:"' . "\0" . '*' . "\0" . 'code";i:4;s:7:"' . "\0" . '*' . "\0" . 'file";s:75:"/home/smortensen/Projects/github.com/smortensen/cinnabari/src/Exception.php";s:7:"' . "\0" . '*' . "\0" . 'line";i:124;s:16:"' . "\0" . 'Exception' . "\0" . 'trace";a:5:{i:0;a:6:{s:4:"file";s:95:"/home/smortensen/Projects/github.com/Spencer-Mortensen/testphp/src/Test.php(58) : eval()\'d code";s:4:"line";i:57;s:8:"function";s:15:"unknownProperty";s:5:"class";s:25:"Datto\\Cinnabari\\Exception";s:4:"type";s:2:"::";s:4:"args";a:2:{i:0;s:8:"Database";i:1;s:7:"unknown";}}i:1;a:3:{s:4:"file";s:75:"/home/smortensen/Projects/github.com/Spencer-Mortensen/testphp/src/Test.php";s:4:"line";i:58;s:8:"function";s:4:"eval";}i:2;a:6:{s:4:"file";s:78:"/home/smortensen/Projects/github.com/Spencer-Mortensen/testphp/src/Command.php";s:4:"line";i:55;s:8:"function";s:3:"run";s:5:"class";s:12:"TestPhp\\Test";s:4:"type";s:2:"->";s:4:"args";a:0:{}}i:3;a:6:{s:4:"file";s:78:"/home/smortensen/Projects/github.com/Spencer-Mortensen/testphp/src/Command.php";s:4:"line";i:41;s:8:"function";s:7:"getTest";s:5:"class";s:15:"TestPhp\\Command";s:4:"type";s:2:"->";s:4:"args";a:2:{i:0;s:1715:"define(\'TESTPHP_TESTS_DIRECTORY\', \'/home/smortensen/Projects/github.com/smortensen/cinnabari/testphp\');

spl_autoload_register(
	function ($class) {
		$namespacePrefix = \'TestPhp\\\\\';
		$namespacePrefixLength = strlen($namespacePrefix);

		if (strncmp($class, $namespacePrefix, $namespacePrefixLength) !== 0) {
			return;
		}

		$relativeClassName = substr($class, $namespacePrefixLength);
		$filePath = dirname(__DIR__) . \'/src/\' . strtr($relativeClassName, \'\\\\\', \'/\') . \'.php\';

		if (is_file($filePath)) {
			include $filePath;
		}
	}
);

spl_autoload_register(
	function ($path)
	{
		$mockPrefix = \'TestPhp\\\\Mock\\\\\';
		$mockPrefixLength = strlen($mockPrefix);

		if (strncmp($path, $mockPrefix, $mockPrefixLength) !== 0) {
			return;
		}

		$parentClass = substr($path, $mockPrefixLength);

		$mockBuilder = new \\TestPhp\\MockBuilder($mockPrefix, $parentClass);
		$mockCode = $mockBuilder->getMock();

		eval($mockCode);
	}
);

require TESTPHP_TESTS_DIRECTORY . \'/autoload.php\';

use Datto\\Cinnabari\\Exception;
use Datto\\Cinnabari\\Language\\Types;
use Datto\\Cinnabari\\Parser\\Tokens\\ParameterToken as ParserParameterToken;
use Datto\\Cinnabari\\Parser\\Tokens\\PropertyToken as ParserPropertyToken;
use TestPhp\\Mock\\Datto\\Cinnabari\\Language\\Functions;
use TestPhp\\Mock\\Datto\\Cinnabari\\Language\\Properties;
use Datto\\Cinnabari\\Resolver;
use Datto\\Cinnabari\\Resolver\\Request;
use Datto\\Cinnabari\\Resolver\\Tokens\\PropertyToken as ResolverPropertyToken;

$functions = new Functions();
$properties = new Properties(array());

$input = new ParserPropertyToken(array(\'unknown\'));

\\TestPhp\\Agent::record(array($properties, \'getType\'), array(\'Database\', \'unknown\'), array(1, Exception::unknownProperty(\'Database\', \'unknown\')));";i:1;b:0;}}i:4;a:6:{s:4:"file";s:74:"/home/smortensen/Projects/github.com/Spencer-Mortensen/testphp/bin/testphp";s:4:"line";i:29;s:8:"function";s:11:"__construct";s:5:"class";s:15:"TestPhp\\Command";s:4:"type";s:2:"->";s:4:"args";a:0:{}}}s:19:"' . "\0" . 'Exception' . "\0" . 'previous";N;}";s:46:"' . "\0" . 'TestPhp\\Packager\\Packages\\Package' . "\0" . 'packageType";i:1;}}}}');

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

$resolver = new Resolver($functions, $properties);
$output = $resolver->resolve($input);
