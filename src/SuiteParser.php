<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of Lens.
 *
 * Lens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Lens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Lens. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens;

use SpencerMortensen\RegularExpressions\Re;
use SpencerMortensen\Parser\String\Lexer;
use SpencerMortensen\Parser\String\Parser;
use SpencerMortensen\Parser\String\Rules;

/*
project: {
	"name": <name>
	"suites": {
		<file>: <suite>
	},
	"summary": {
		"passed": ...,
		"failed": ...
	}
}

suite: {
	"namespace": "...",
	"uses": {
		<alias>: <namespace>
	},
	"tests": {
		<line>: <test>
	},
	"summary": {
		"passed": ...
		"failed": ...
	}
}

test: {
	"actual": "...",
	"cases": {
		<line>: <case>
	}
	"summary": {
		"passed": ...
		"failed": ...
	}
}

case: {
	"text": "...",
	"code": {
		"fixture": "...",
		"expected": "...",
		"script": [...]
	},
	"results": <results>|null,
	"summary": {
		"pass": false|true,
		"issues": "..."|null
	}
}

results: {
	"expected": {
		"pre": <state>|null,
		"post": <state>|null,
		"diff": <state>|null
	},
	"actual": {
		"pre": <state>|null,
		"post": <state>|null,
		"diff": <state>|null
	}
}
*/

class SuiteParser extends Parser
{
	/** @var string */
	private $input;

	public function __construct()
	{
		$grammar = <<<'EOS'
suite: AND phpTag optionalNamespace optionalUses tests
phpTag: AND phpTagLine optionalComments
phpTagLine: RE <\?php\s+
optionalComments: MANY comment 0
comment: RE /\*.*?\*/\s*
optionalNamespace: MANY namespace 0 1
namespace: AND namespaceLine optionalComments
namespaceLine: RE namespace\h+([a-zA-Z_0-9\\]+);\s*
optionalUses: MANY use 0
use: AND useLine optionalComments
useLine: RE use\h+(?<namespace>[a-zA-Z_0-9\\]+)(?:\h+as\h+(?<alias>[a-zA-Z_0-9]+))?;\s*
tests: MANY test 1
test: AND subject cases
subject: AND subjectLabel optionalComments lineNumber code
subjectLabel: RE // Test\s+
lineNumber: STRING
code: MANY codeBlock 1
codeBlock: AND codeUnit optionalComments
codeUnit: RE (?!(?:// (?:Test|Input|Output))|/\*|$).+?(?=(?:// (?:Test|Input|Output))|/\*|$)
cases: MANY case 1
case: AND optionalInput output
optionalInput: MANY input 0 1
input: AND inputLabel optionalComments code
inputLabel: RE // Input\s+
output: AND outputLabel optionalComments lineNumber code
outputLabel: RE // Output\s+
EOS;

		$rules = new Rules($this, $grammar);
		$rule = $rules->getRule('suite');

		parent::__construct($rule);
	}

	public function parse($input)
	{
		$this->input = $input;

		return parent::parse($input);
	}

	public function getSuite(array $match)
	{
		$namespace = $match[1];
		$uses = $match[2];
		$tests = $match[3];

		return array(
			'namespace' => $namespace,
			'uses' => $uses,
			'tests' => $tests
		);
	}

	public function getOptionalNamespace(array $matches)
	{
		return array_shift($matches);
	}

	public function getNamespace(array $matches)
	{
		return $matches[0][1];
	}

	public function getOptionalUses(array $matches)
	{
		return self::merge($matches);
	}

	private static function merge(array $input)
	{
		$output = array();

		foreach ($input as $array) {
			$output += $array;
		}

		return $output;
	}

	public function getUse(array $matches)
	{
		return $matches[0];
	}

	public function getUseLine(array $match)
	{
		$namespace = $match['namespace'];
		$alias = &$match['alias'];

		if ($alias === null) {
			$alias = self::getAliasName($namespace);
		}

		return array(
			$alias => $namespace
		);
	}

	private static function getAliasName($namespace)
	{
		$slash = strrpos($namespace, '\\');

		if (is_integer($slash)) {
			return substr($namespace, $slash + 1);
		}

		return $namespace;
	}

	public function getTests(array $matches)
	{
		return self::merge($matches);
	}

	public function getTest(array $matches)
	{
		list($subject, $cases) = $matches;
		list($line, $code) = $subject;

		return array(
			$line => array(
				'code' => $code,
				'cases' => $cases
			)
		);
	}

	public function getLineNumber()
	{
		/** @var Lexer $lexer */
		$lexer = $this->getState();
		$position = $lexer->getPosition();
		$text = substr($this->input, 0, $position);
		return substr_count($text, "\n") + 1;
	}

	public function getSubject(array $matches)
	{
		$line = $matches[2];
		$code = $matches[3];

		return array($line, $code);
	}

	public function getCode(array $matches)
	{
		return implode("\n", $matches);
	}

	public function getCodeBlock(array $matches)
	{
		return $matches[0];
	}

	public function getCodeUnit($value)
	{
		return trim($value);
	}

	public function getCases(array $matches)
	{
		return self::merge($matches);
	}

	public function getCase(array $match)
	{
		$input = $match[0];
		$line = $match[1][0];
		$output = $match[1][1];

		$expected = $output;
		$script = self::extractScript($expected);

		return array(
			$line => array(
				'input' => array(
					'text' => $input,
					'code' => $input
				),
				'output' => array(
					'text' => $output,
					'code' => $expected
				),
				'script' => $script
			)
		);
	}

	private static function extractScript(&$php)
	{
		$script = array();

		$lines = Re::split('\\r?\\n', $php);
		$expression = '^(?<call>(?:\\$[a-zA-Z_0-9]+->)?[a-zA-Z_0-9]+\(.*?\);)(?:\\s*//\\s+(?<body>.*?))?$';

		foreach ($lines as &$line) {
			if (!Re::match($expression, $line, $match)) {
				continue;
			}

			$function = &$match['call'];
			$body = &$match['body'];

			$line = $function;
			$script[] = $body;
		}

		$php = implode("\n", $lines);

		return $script;
	}

	public function getOptionalInput(array $matches)
	{
		return array_shift($matches);
	}

	public function getInput(array $matches)
	{
		return $matches[2];
	}

	public function getOutput(array $matches)
	{
		$line = $matches[2];
		$code = $matches[3];

		return array($line, $code);
	}
}
