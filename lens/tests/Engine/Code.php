<?php

namespace Lens\Engine;

require LENS . '/bootstrap.php';

// Test
$code = new Code();
$code->setCode($code);
$code->setMode(Code::MODE_PLAY);

$processor = new Processor();
$processor->run(0, $code);
$processor->getResult($id, $output);


// Input
$code = <<<'EOS'
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
$output = array();
