<?php

namespace TestPhp;

use TestPhp\Archivist\Archivist;

require TESTPHP . '/bootstrap.php';
require TESTPHP . '/src/Test.php';

// Test
$test = new Test($input, false);
$test->run();


// Input
$input = <<<'EOS'
$null = null;
$boolean = true;
$integer = 3;
$float = 3.14159;
$string = "Hey\nthere";
$GLOBALS['planet'] = 'Earth';
define('pi', 3.14159265359);
echo "Hello\n";
EOS;

// Output
\Example\send(
	array(
		'variables' => array(
			'boolean' => true,
			'float' => 3.14159,
			'integer' => 3,
			'null' => null,
			'string' => "Hey\nthere"
		),
		'globals' => array(
			'planet' => 'Earth'
		),
		'constants' => array(
			'TESTPHP' => TESTPHP,
			'pi' => 3.14159265359
		),
		'output' => "Hello\n",
		'calls' => array(),
		'exception' => null,
		'errors' => array(),
		'fatalError' => null
	)
);

$GLOBALS['planet'] = 'Earth';
define('pi', 3.14159265359);
