<?php

namespace _Lens\Lens\Tests;

require_once __DIR__ . '/lens/autoload.php';

$input = <<<'EOS'
<?php

namespace Example;

use btree;


// Test
$cache = new Cache();
$result = $cache->set($key, $value);

// Cause
$key = 'x';
$value = 'b';

// Effect
$btree = btree::open(new btree(), new btree()); // return new btree();
$btree->set('x', 'b'); // return true;
$result = 'blue';
EOS;

$parser = new SuiteParser();
$output = $parser->parse($input);

echo "output: ", json_encode($output), "\n";
