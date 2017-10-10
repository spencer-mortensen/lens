<?php

namespace Lens;

require 'autoload.php';

$basePath = '/tmp';
$relativePath = '../../../tmp-directory';

$base = new Base($basePath);
$absolutePath = $base->getAbsolutePath($relativePath);

echo "absolutePath: ", json_encode($absolutePath), "\n";
