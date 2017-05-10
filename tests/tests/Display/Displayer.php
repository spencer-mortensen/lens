<?php

namespace TestPhp\Display;

require __DIR__ . '/../../bootstrap.php';


// Cause
$x = Format::show(null);

// Effect
$x = 'null';


// Cause
$x = Format::show(true);

// Effect
$x = 'true';


// Cause
$x = Format::show(false);

// Effect
$x = 'false';


// Cause
$x = Format::show(0);

// Effect
$x = '0';


// Cause
$x = Format::show(12);

// Effect
$x = '12';


// Cause
$x = Format::show(-6);

// Effect
$x = '-6';


// Cause
$x = Format::show(3.14159);

// Effect
$x = '3.14159';


// Cause
$x = Format::show(-1.0);

// Effect
$x = '-1';


// Cause
$x = Format::show('Lorem ipsum');

// Effect
$x = '\'Lorem ipsum\'';


// Cause
$x = Format::show("Lorem ipsum\n");

// Effect
$x = '"Lorem ipsum\\n"';


// Cause
$x = Format::show(array(0 => 'a'));

// Effect
$x = "array(\n\t'a'\n)";


// Cause
$x = Format::show(array(1 => 'a'));

// Effect
$x = "array(\n\t1 => 'a'\n)";


// Cause
$x = Format::show(array('a' => 'A', 'b' => 'B'));

// Effect
$x = "array(\n\t'a' => 'A',\n\t'b' => 'B'\n)";


// Cause
$x = Format::show(new \stdClass());

// Effect
$x = 'object(#1:stdClass)';

// Cause
// $x = Format::show(fopen('php://stdout', 'r'));

// Effect
// $x = 'resource(#8:stream)';
