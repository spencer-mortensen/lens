<?php

namespace _Lens\Lens\Php;

use InvalidArgumentException;

require_once __DIR__ . '/lens/autoload.php';

$input = <<<'EOS'
\Namespace\Class
EOS;

try {
	$lexer = new Lexer();
	$nodes = $lexer->getNodes($input);
	echo var_export($nodes), "\n";
} catch (InvalidArgumentException $exception) {
	echo "not a match\n";
}
