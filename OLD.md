=== tests ===

$project:
	"name": ...
	"suites":
		$file: $suite,
		...

$suite:
	"namespace": ...
	"uses": [...]
	"tests":
		$line: $test,
		...

$test:
	"test": ...,
	"cases":
		$line: $case,
		...

$case:
	"cause": ...
	"effect": ...


=== results ===

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


=== summary ===

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
