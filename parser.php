<?php

namespace _Lens\Lens\Php;

use InvalidArgumentException;

require_once __DIR__ . '/lens/autoload.php';

$input = <<<'EOS'
Example\Birds
EOS;

try {
	$parser = new Parser();
	$parser->parse($input);
} catch (InvalidArgumentException $exception) {
	echo "not a match\n";
}
