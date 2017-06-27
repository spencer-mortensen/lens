<?php

namespace TestPhp;

require 'autoload.php';
require 'Person.php';

$mockBuilder = new MockBuilder('TestPhp\\Mock\\', 'Person');

$code = $mockBuilder->getMock();
echo $code, "\n";
