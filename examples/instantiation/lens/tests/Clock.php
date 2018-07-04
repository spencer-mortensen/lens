<?php

namespace Example;

use DateTime;

// Test
$clock = new Clock();
$clock->getTime();

// Effect
$time = new DateTime();
$time->format('g:i a'); // return '6:35 am';

// Effect
$time = new DateTime();
$time->format('g:i a'); // return '12:01 pm';
