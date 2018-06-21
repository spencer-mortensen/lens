=== tests.json ===

"project":
	"name": ...
	"suites": {
		$file: $suite,
		...
	}

$suite:
	"namespace": ...
	"uses": [...]
	"tests": {
		$line: $test,
		...
	}

$test:
	"code": ...,
	"cases": {
		$line: $case,
		...
	}

$case:
	"cause": ...
	"effect": ...
	"script": [...]
	"issues": [$issue, ...]
	"coverage": $coverage

$issue:
	"-": "$c = '.nn';"
	"+": "$c = '.';"

$coverage:
	"classes": {
		$class: [$lineNumber, ...],
		...
	},
	"functions": {
		$function: [$lineNumber, ...],
		...
	},
	"traits": {
		$trait: [$lineNumber, ...],
		...
	}
