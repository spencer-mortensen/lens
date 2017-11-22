<?php

namespace Lens;


// Test
$filesystem = new Filesystem();
$contents = $filesystem->read($path);

// Input
$path = '/tmp/file.txt';

// Output
$contents = 'Text';