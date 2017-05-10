# TestPHP

*Warning: this code is not yet ready to use.*

This repository will become a unit-test framework for PHP.

======

// Code:
public function f(Filesystem $filesystem, $path)
{
    return $filesystem->read($path);
}

======

use Application\Filesystem;

$filesystem = new Filesystem();

// Actual
$x = f($filesystem, '/tmp/smm.txt');

// Expected
$x = 'hey';

======

use TestPhp\Mock\Application\Filesystem;

$filesystem = new Filesystem();

// Actual
$x = f($filesystem, '/tmp/smm.txt');

// Expected
$filesystem->read('/tmp/smm.txt'); // 'hey'
$x = 'hey';

======
