<?php

namespace Example;


// Test
$filesystem = new Filesystem();
$contents = $filesystem->read($path);

// Input
$path = '/tmp/file.txt';

// Output
is_dir($path); // return false;
file_get_contents($path); // return 'File contents';
$contents = 'File contents';
