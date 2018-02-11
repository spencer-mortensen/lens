<?php

namespace Example;

use RangeException;


// Test
$math = new Math();
$c = $math->add(1, 1);

// Output
$c = 2;


// Test
$math = new Math();
$c = $math->divide($a, $b);

// Input
$a = 1;
$b = 2;

// Output
$c = 0.5;

// Input
$a = 1;
$b = 0;

// Output
throw new RangeException();
