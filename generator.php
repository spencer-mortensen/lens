<?php

namespace _Lens\Lens\Php;

require_once __DIR__ . '/lens/autoload.php';

$rules = <<<'EOS'
Name: MAKE NamePath name
NamePath: AND Identifier NameSegments
NameSegments: ALL NameSegment
NameSegment: AND Backslash Identifier
Backslash: READ \
Identifier: READ identifier
EOS;

$generator = new ParserGenerator();
$php = $generator->generate($rules);

echo $php, "\n";
