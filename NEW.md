=== ANALYZE ===

A. CODE (build the source-code cache):

	* Watch for new / modified / deleted files (in "src" and "vendor")
	* Run a static parser to build the cache


B. TESTS (build the tests cache):

	* Watch for new / modified / deleted files
	* Run a static parser to build the cache


1. PHP_LEXER (create all clean PHP tokens, including meta tokens):
$phpTokens = [{"379":"<?php\n"}, {"382":"\n"}, {"388":"namespace"}, ...];

2. FILE_LEXER (group all clean tokens):
[
	{PREAMBLE: {"tokens": [...], "origin": [0, 0]} },
	{MOCKS: {"tokens": [...], "origin": [0, 12]} },
	{SUBJECT: {"tokens": [...], "origin": [0, 14]} },
	{CAUSE: {"tokens": [...], "origin": [0, 17]} },
	{EFFECT: {"tokens": [...], "origin": [0, 23]} }
]

3. FILE_PARSER:
{
	"preamble": {"tokens": [...], "origin": [0, 0]},
	"mocks": {"tokens": [...], "origin": [0, 12]},
	"tests": {
		$subjectLine: {
			"subject": {"tokens": [...], "origin": [0, 14]},
			"cases": {
				$line: {
					"cause": null,
					"effect": {"tokens": [...], "origin": [0, 23]}
				}
			}
		}
	}
}

4. SECTIONS:
{
	"preamble": {
		"namespace": $name,
		"uses": {$alias: $name, ...}
	},
	"mocks": {$variable: $name, ...},
	"tests": {
		$subjectLine: {
			"subject": $php,
			"cases": {
				$line: {
					"cause": $php,
					"effect": $php
				}
			}
		}
	}
}

(cacheable)


=== EVALUATE ===

a. CODE COVERAGE (identify the executable lines of code--just for the "src" files)

b. TESTS

$results:
	$file:
		$testLine:
			$caseLine:
				"actual":
					"pre": $state
					"post": $state
				"expected":
					"pre": $state
					"post": $state
				"coverage: $coverage

$coverage:
	"classes":
		$class: [$lineNumber, ...]
		...
	"functions":
		$function: [$lineNumber, ...]
		...
	"traits":
		$trait: [$lineNumber, ...]
		...


=== SUMMARIZE ===

$summary:
	$file:
		$testLine:
			$caseLine:
				"pass": true|false
				"issues": [$issue, ...]
				"coverage": $coverage

$issue:
	"-": "$c = '...';"
	"+": "$c = '...';"

$coverage:
	"classes":
		$class: [$lineNumber, ...]
		...
	"functions":
		$function: [$lineNumber, ...]
		...
	"traits":
		$trait: [$lineNumber, ...]
		...

======



QUESTIONS

A.
$mock->method($x); // $x = new LiveClass();


QUESTIONS

A.
throw new Exception(new LiveClass());

B.
$live = new LiveClass();
throw new Exception($live);


# EXPECTED

# null
null

# true
true

# 1
1

# 3.14
3.14

# 'text'
'text'

# ['a' => 'A']
[ 'ARRAY', ['a' => 'A'] ]

# $x
[ 'VARIABLE' => 'x' ]

# global $x;
[ 'GLOBAL' => 'x' ]

# ['a' => $x]
[ 'ARRAY' => ['a' => ['VARIABLE', 'x']] ]

# $x = null;
[ 'SET' => [['VARIABLE', 'x'], 'null'] ]

# define('name', $x);
[ 'CONSTANT' => ['name', ['VARIABLE', 'x'], false] ]

# define('name', $x, true);
[ 'CONSTANT' => ['name', ['VARIABLE', 'x'], true] ]

# echo "hi";
[ 'ECHO' => 'hi' ]

# new A\Mock(...)
[ 'CALL' => ['A\\Mock', '__construct', [...], null] ]

# $x->method($a, $b);
[ 'CALL' => [['VARIABLE', 'x'], 'method', [['VARIABLE', 'a'], ['VARIABLE', 'b']], null] ]

# A\Mock::method(...);
[ 'CALL' => ['A\\Mock', 'method', [...], null] ]

# A\mockFunction(...)
[ 'CALL' => [null, 'A\\mockFunction', [...], null] ]

# throw $x;
[ 'THROW' => ['VARIABLE', 'x'] ]

# throw new InvalidArgumentException(...);
[ 'THROW' => [ 'NEW', 'InvalidArgumentException', [...] ] ]

# return 1;
[ 'RETURN' => '1' ]


// Mocks
$x = new Mock();


// Effect
global $x;
$x = [null, true, 1, 3.14, 'text', ['a' => 'A'], $x];
define('name', $x[, true]);
echo "hi";

($x = )new Mock|Live();
($x = )$x->method(...); // ...
($x = )Mock|Live::method(...); // ...
($x = )mockFunction|liveFunction(...); // ...

throw new InvalidArgumentException(...);


// Script
// global $x; $x = 1; define('name', $x[, true]); echo "hi";
// ... return 1;
// ... throw new Exception(...);


# ACTUAL

// Mocks
$x = new Mock();

// Cause
<PHP> <-- load every live function (until function autoloaders become available)

// Subject
<PHP> <-- load every live function (until function autoloaders become available)


=== LENS ===

// Mocks
$a = new Person();
$b = new A\Person();
$c = new \B\Person();

// Subject
$x = $a;

// Cause
$a = 1;

// Effect
$x = 1;

=== EXPECTED ===

// Mocks
$a = new Person();
$b = new A\Person();
$c = new \B\Person();

// Effect
$x = 1;

=== ACTUAL ===

// Mocks
$a = new Person();
$b = new A\Person();
$c = new \B\Person();

// Cause
$a = 1;

// Subject
$x = $a;

======
