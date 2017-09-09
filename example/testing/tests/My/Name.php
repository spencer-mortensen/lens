<?php

namespace Example\My;

require LENS . 'bootstrap.php';

// Test
$name = new Name($first, $last);
$fullName = $name->getFullName();

// Input
$first = 'Spencer';
$last = 'Mortensen';

// Output
$fullName = 'Spencer Mortensen';
