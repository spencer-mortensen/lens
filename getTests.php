<?php

namespace _Lens\Lens\Tests;

use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

require_once __DIR__ . '/lens/autoload.php';

$filesystem = new Filesystem();
$tests = Path::fromString('/home/smortensen/Projects/example/lens/tests');
$paths = [
	Path::fromString('/home/smortensen/Projects/example/lens/tests')
];

$suites = new GetSuites($tests, $filesystem);
$output = $suites->getSuites($paths);

echo "output: ", json_encode($output), "\n";
