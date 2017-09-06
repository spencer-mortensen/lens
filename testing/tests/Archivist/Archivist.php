<?php

namespace Lens;

use Lens\Archivist\Archivist;
use Lens\Archivist\Archives\ObjectArchive;
use Lens\Archivist\Archives\ResourceArchive;

require LENS . '/bootstrap.php';


// Test
$archivist = new Archivist();
$output = $archivist->archive($input);


// Input
$input = null;

// Output
$output = null;


// Input
$input = true;

// Output
$output = true;


// Input
$input = false;

// Output
$output = false;


// Input
$input = 0;

// Output
$output = 0;


// Input
$input = 12;

// Output
$output = 12;


// Input
$input = -6;

// Output
$output = -6;


// Input
$input = 3.14159;

// Output
$output = 3.14159;


// Input
$input = -1.0;

// Output
$output = -1.0;


// Input
$input = "Lorem ipsum\n";

// Output
$output = "Lorem ipsum\n";


// Input
$input = array('a' => 'A', 'b' => 'B', 'Z');

// Output
$output = array('a' => 'A', 'b' => 'B', 'Z');


/*
// Input
$input = new \stdClass();

// Output
$output = new ObjectArchive(
	spl_object_hash($input),
	get_class($input),
	array()
);
*/


/*
// Input
$input = new \Example\Archivist\Object\Loop();

// Output
$output = new ObjectArchive(
	spl_object_hash($input),
	'Example\\Archivist\\Object\\Loop',
	array()
);
*/


// Input
$input = fopen('php://memory', 'w+');

// Output
$output = new ResourceArchive(
	(integer)$input,
	get_resource_type($input)
);
