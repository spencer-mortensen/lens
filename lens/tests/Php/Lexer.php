<?php

namespace _Lens\Lens\Php;

use InvalidArgumentException;


// Test
$lexer = new Lexer();
$nodes = $lexer->getNodes($php);

// Cause
$php = " \t\r\n";

// Effect
$nodes = [new Node($php, ['whitespace'])];

// Cause
$php = "azAZ_09\x7F\xFF";

// Effect
$nodes = [new Node($php, ['identifier'])];

/*
// Cause
$php = "\x7e";

// Effect
throw new InvalidArgumentException();
*/

// Cause
$php = '0';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '12';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '0x0';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '0xf';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '0xg';

// Effect
$nodes = [new Node('0', ['integer', 'value']), new Node('xg', ['identifier'])];

// Cause
$php = '0X0F';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '00';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '07';

// Effect
$nodes = [new Node($php, ['integer', 'value'])];

// Cause
$php = '08';

// Effect
$nodes = [new Node('0', ['integer', 'value']), new Node('8', ['integer', 'value'])];

// Cause
$php = '0b0';

// Effect
$nodes = [new Node('0b0', ['integer', 'value'])];

// Cause
$php = '0b1';

// Effect
$nodes = [new Node('0b1', ['integer', 'value'])];

// Cause
$php = '0b2';

// Effect
$nodes = [new Node('0', ['integer', 'value']), new Node('b2', ['identifier'])];

// Cause
$php = '0B01';

// Effect
$nodes = [new Node('0B01', ['integer', 'value'])];

// Cause
$php = '0.';

// Effect
$nodes = [new Node($php, ['float', 'value'])];

// Cause
$php = '.0';

// Effect
$nodes = [new Node($php, ['float', 'value'])];

// Cause
$php = '0.0';

// Effect
$nodes = [new Node($php, ['float', 'value'])];

// Cause
$php = '3.14159e0';

// Effect
$nodes = [new Node($php, ['float', 'value'])];

// Cause
$php = '3.14159E0';

// Effect
$nodes = [new Node($php, ['float', 'value'])];


// Cause
$php = '3.14159e+0';

// Effect
$nodes = [new Node($php, ['float', 'value'])];

// Cause
$php = '3.14159e-0';

// Effect
$nodes = [new Node($php, ['float', 'value'])];

/* TODO: test the strings!

// Cause
$php = <<<'EOS'
''
EOS;

// Effect
$nodes = [new Node($php, ['string', 'value'])];

// Cause
$php = <<<'EOS'
'\\'
EOS;

// Effect
$nodes = [new Node($php, ['string', 'value'])];
*/

// Cause
$php = '// Comment';

// Effect
$nodes = [new Node('// Comment', ['comment'])];

// Cause
$php = "// Comment\n";

// Effect
$nodes = [new Node('// Comment', ['comment']), new Node("\n", ['whitespace'])];

// Cause
$php = '# Comment';

// Effect
$nodes = [new Node('# Comment', ['comment'])];

// Cause
$php = "# Comment\n";

// Effect
$nodes = [new Node('# Comment', ['comment']), new Node("\n", ['whitespace'])];

// Cause
$php = '/* Comment */';

// Effect
$nodes = [new Node('/* Comment */', ['comment'])];

// Cause
$php = "/*\n * Comment\n */";

// Effect
$nodes = [new Node("/*\n * Comment\n */", ['comment'])];

// Cause
$php = "/* Comment */\n";

// Effect
$nodes = [new Node('/* Comment */', ['comment']), new Node("\n", ['whitespace'])];

// Cause
$php = '/* a *//* b */';

// Effect
$nodes = [new Node('/* a */', ['comment']), new Node('/* b */', ['comment'])];

// Cause
$php = '<?php';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '===';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '!==';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<=>';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '**=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<<=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '>>=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '->';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '||';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '&&';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '??';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '==';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '!=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<>';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '>=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '=>';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '++';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '--';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '.=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '+=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '*=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '/=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '%=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '^=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '|=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '%=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '^=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '|=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '&=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '**';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<<';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '>>';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<?';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '?>';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '$';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = ';';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '(';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = ')';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '[';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = ']';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '{';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '}';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '\\';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '=';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = ',';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '.';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '+';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '-';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '*';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '/';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '%';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '^';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '|';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '&';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '<';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '>';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '!';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '?';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = ':';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '~';

// Effect
$nodes = [new Node($php, ['symbol'])];

// Cause
$php = '@';

// Effect
$nodes = [new Node($php, ['symbol'])];
