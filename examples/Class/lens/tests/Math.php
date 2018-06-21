<?php

namespace Example;

use RangeException;


// Test
$math = new Math();
$c = $math->add(1, 1);

// Effect
$c = 2;


// Test
$math = new Math();
$c = $math->divide($a, $b);

// Cause
$a = 1;
$b = 2;

// Effect
$c = 0.5;

// Cause
$a = 1;
$b = 0;

// Effect
throw new RangeException();
