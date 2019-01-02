CLASS NAMESPACE:
	classes
	traits
	interfaces

FUNCTION NAMESPACE:
	functions

CONSTANTS NAMESPACE:
	constants

======

Function Calls:

1. For any unsafe relative function in a namespace (non-empty namespace, no alias, unsafe function): TAKE NOTE
	"time();"
		* Make a note of the loose EITHER "Example\time" OR "Lens\time" function dependency.
		* May need to add an alias at run time: "use Lens\time as time;"

 * Make a note of any function dependencies. (e.g. "namespace Example; A\time();" => "Example\A\time")



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

2. Protect annotations when they appear before a definition.

======

sources:
	src/
		modified.json

		MyClass.json:
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
	json
		data

	user
		real aliases
		real code

	live
		safe aliases
		real code

	mock
		fake aliases
		fake code

new SourcePaths($core, $cache)
	->getCoreClassPath()
