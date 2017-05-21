<?php

namespace TestPhp\Display;

require TESTPHP_TESTS_DIRECTORY . '/bootstrap.php';

$displayer = new Displayer();


// Test
$x = $displayer->display(null);

// Expected
$x = 'null';


// Test
$x = $displayer->display(true);

// Expected
$x = 'true';


// Test
$x = $displayer->display(false);

// Expected
$x = 'false';


// Test
$x = $displayer->display(0);

// Expected
$x = '0';


// Test
$x = $displayer->display(12);

// Expected
$x = '12';


// Test
$x = $displayer->display(-6);

// Expected
$x = '-6';


// Test
$x = $displayer->display(3.14159);

// Expected
$x = '3.14159';


// Test
$x = $displayer->display(-1.0);

// Expected
$x = '-1';


// Test
$x = $displayer->display('Lorem ipsum');

// Expected
$x = '\'Lorem ipsum\'';


// Test
$x = $displayer->display("Lorem ipsum\n");

// Expected
$x = '"Lorem ipsum\\n"';


// Test
$x = $displayer->display(array(1 => array(0 => 'a')));

// Expected
$x = "array('a')";


// Test
$x = $displayer->display(array(1 => array(1 => 'a')));

// Expected
$x = "array(1 => 'a')";


// Test
$x = $displayer->display(array(1 => array('a' => 'A', 'b' => 'B')));

// Expected
$x = "array('a' => 'A', 'b' => 'B')";


// Test
// $x = $displayer->display(new \stdClass());

// Expected
// $x = 'object(#1:stdClass)';


// Test
// $x = $displayer->display(fopen('php://stdout', 'r'));

// Expected
// $x = 'resource(#8:stream)';
