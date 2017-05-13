<?php

namespace TestPhp\Display;

require TESTPHP_TESTS_DIRECTORY . '/bootstrap.php';

$displayer = new Displayer();


// Cause
$x = $displayer->display(null);

// Effect
$x = 'null';


// Cause
$x = $displayer->display(true);

// Effect
$x = 'true';


// Cause
$x = $displayer->display(false);

// Effect
$x = 'false';


// Cause
$x = $displayer->display(0);

// Effect
$x = '0';


// Cause
$x = $displayer->display(12);

// Effect
$x = '12';


// Cause
$x = $displayer->display(-6);

// Effect
$x = '-6';


// Cause
$x = $displayer->display(3.14159);

// Effect
$x = '3.14159';


// Cause
$x = $displayer->display(-1.0);

// Effect
$x = '-1';


// Cause
$x = $displayer->display('Lorem ipsum');

// Effect
$x = '\'Lorem ipsum\'';


// Cause
$x = $displayer->display("Lorem ipsum\n");

// Effect
$x = '"Lorem ipsum\\n"';


// Cause
$x = $displayer->display(array(1 => array(0 => 'a')));

// Effect
$x = "array('a')";


// Cause
$x = $displayer->display(array(1 => array(1 => 'a')));

// Effect
$x = "array(1 => 'a')";


// Cause
$x = $displayer->display(array(1 => array('a' => 'A', 'b' => 'B')));

// Effect
$x = "array('a' => 'A', 'b' => 'B')";


// Cause
// $x = $displayer->display(new \stdClass());

// Effect
// $x = 'object(#1:stdClass)';

// Cause
// $x = $displayer->display(fopen('php://stdout', 'r'));

// Effect
// $x = 'resource(#8:stream)';
