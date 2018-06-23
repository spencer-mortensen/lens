<?php

namespace Example;

// Test
$greeter = new Greeter();
$output = $greeter->greet($person);

// Cause
$person = new Person();

// Effect
$person->getName(); // return 'Ann';
time(); // return 1518302651;
$output = "Hello Ann, it's Saturday today!";
