<?php

namespace Lens;

use Lens\Archivist\Archivist;


// Test
$displayer = new Displayer();
$output = $displayer->display($input);


// Input
$input = null;

// Output
$output = 'null';


// Input
$input = true;

// Output
$output = 'true';


// Input
$input = false;

// Output
$output = 'false';


// Input
$input = 0;

// Output
$output = '0';


// Input
$input = 12;

// Output
$output = '12';


// Input
$input = -6;

// Output
$output = '-6';


// Input
$input = 3.14159;

// Output
$output = '3.14159';


// Input
$input = -1.0;

// Output
$output = '-1';


// Input
$input = 'Lorem ipsum';

// Output
$output = '\'Lorem ipsum\'';


// Input
$input = "Lorem ipsum\n";

// Output
$output = '"Lorem ipsum\\n"';


// Input
$input = array(0 => 'a');

// Output
$output = "array('a')";


// Input
$input = array(1 => 'a');

// Output
$output = "array(1 => 'a')";


// Input
$input = array('a' => 'A', 'b' => 'B');

// Output
$output = "array('a' => 'A', 'b' => 'B')";


// Input
$archivist = new Archivist();
$input = $archivist->archive(new \stdClass());

// Output
$output = 'object(\'stdClass\')';


// Input
$archivist = new Archivist();
$input = $archivist->archive(fopen('php://memory', 'w+'));

// Output
$output = 'resource(stream)';
