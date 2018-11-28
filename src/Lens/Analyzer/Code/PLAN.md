CLASS NAMESPACE:
	classes
	traits
	interfaces

FUNCTION NAMESPACE:
	functions

CONSTANTS NAMESPACE:
	constants

======

In the global namespace:

1. Rewrite any unsafe aliases:
	"use DateTime as D;" => "use Lens\DateTime as D;"
	"use function time as f;" => "use function Lens\time as f;"
		* Make a note of the "Lens\time" function dependency.

2. Rewrite any unsafe absolute paths:
	"new \DateTime();" => "new \Lens\DateTime();"
	"\DateTime::getLastErrors();" => "\Lens\DateTime::getLastErrors();"
	"\time();" => "\Lens\time();"
		* Make a note of the "Lens\time" function dependency.

3. Rewrite any unsafe relative paths:
	"new DateTime();" => "new \Lens\DateTime();"
	"DateTime::getLastErrors();" => "Lens\DateTime::getLastErrors();"
	"time();" => "\Lens\time();"
		* Make a note of the "Lens\time" function dependency.


In a non-empty namespace:

1. Rewrite any unsafe aliases:
	"use DateTime;" => "use Lens\DateTime as DateTime;"
	"use function time;" => "use function Lens\time as time;"
		* Make a note of the "Lens\time" function dependency.

2. Rewrite any unsafe absolute paths:
	"new \DateTime();" => "new \Lens\DateTime();"
	"\DateTime::getLastErrors();" => "\Lens\DateTime::getLastErrors();"
	"\time();" => "\Lens\time();"
		* Make a note of the "Lens\time" function dependency.

3. Unsafe bare functions, without a use-statement, can pass-through to the global scope:
	"namespace Example; time();" <-- could be either "Example\time" or "time"
		* Make a note of the loose EITHER "Example\time" OR "time" function dependency.

	Prefixed functions, or bare functions WITH a use-statement, cannot pass-through:
		* Make a note of the strict "Example\time" function dependency.

At run time, the autoloader will add a "require_once" statement for each function dependency.
(Later, we'll use a function autoloader.)

If a live "Example\time" exists:
	If "Example\time" is safe:
		Load the live user-defined function
	Otherwise:
		Load the mock user-defined function
Otherwise if we CAN fall-through to the global scope:
	Load the mock global function (this will be the "time" mock, but stored as "Example\time")
Otherwise:
	Load NOTHING. (There will be a run-time error because the function is not defined.)


Extra credit:

1. Handle dynamic function calls. (Dynamic function calls disregard the namespace and use-statement context.)
	"call_user_func('DateTime::getLastErrors')"
	"$x = 'DateTime'; $x::getLastErrors();"

------

X {"92":"function"},{"100":"time"},{"137":"("}

{"100":"time"},{"137":"("},{"138":")"},{"148":";"},

{"126":"\\"},{"100":"time"},{"137":"("}

{"100":"X"},{"83":"::"},{"100":"f"},{"137":"("}
{"100":"self"},{"83":"::"},{"100":"f"}
{"100":"parent"},{"83":"::"},{"100":"f"}

{"127":"new"},{"100":"X"},{"137":"("}

92: FUNCTION_
100: IDENTIFIER_
137: PARENTHESIS_LEFT_
138: PARENTHESIS_RIGHT_
126: NAMESPACE_SEPARATOR_
83: DOUBLE_COLON_
160: VARIABLE_
127: NEW_

======

sources:
	src/
		modified.json

		file.php:
		{
			"classes": [
				"MyClass",
				"Example\MyClass",
				"Example\A\MyClass"
			]
			"functions": [
				...
			],
			...
		}

	...

code:
	classes/
		live/
		mock/

	functions/
		live/
		mock/

	interfaces/
		
	traits/
		live/
		mock/



new SourcePaths($core, $cache)
	->getCoreClassPath()

======

1. NamespaceLexer (extract namespaced sections of code from deflated tokens)
[
	{
		"position": 0, <-- deflatedTokens index
		"tokens": [$phpToken, ...]
	}
]

2. NamespaceParser (extract data from an individual namespaced section)
{
	"namespace": ...,
	"uses": {
		"functions": {
			$alias: $fullName,
			...
		},
		"classes": {
			...
		}
	},
	"definitions": {
		"functions": {
			$name: [$iBegin, $iEnd],
			...
		},
		"classes": ...,
		"interfaces": ...,
		"traits": ...
	}
}

3. Parser (extract data from the whole file)
[
	{
		"namespace": ...,
		"uses": {
			"functions": {
				$alias: $fullName,
				...
			},
			"classes": {
				...
			}
		},
		"definitions": {
			"functions": {
				$name: $tokens,
				...
			},
			"classes": ...,
			"interfaces": ...,
			"traits": ...
		}
	},
	...
]


$this->namespacing = new Namespacing();
$this->namespacing->setContext($data['namespace'], $data['uses']);
	$fullName = $this->namespacing->getAbsoluteFunction($name);
	$fullName = $this->namespacing->getAbsoluteClass($name);