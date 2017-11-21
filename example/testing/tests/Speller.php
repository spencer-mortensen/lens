<?php

namespace Example;


// Test
$speller = new Speller($terminal);
$speller->start();

// Input
$terminal = new Terminal();

// Output
$terminal->write('Type a word:');
$terminal->read(); // return 'cat';
$terminal->write('The word "cat" is spelled: C-A-T!');
