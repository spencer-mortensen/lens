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

namespace Lens_0_0_57\Lens\Tests;

use Lens_0_0_57\SpencerMortensen\RegularExpressions\Re;
use Lens_0_0_57\SpencerMortensen\Parser\Rule;
use Lens_0_0_57\SpencerMortensen\Parser\String\Parser;
use Lens_0_0_57\SpencerMortensen\Parser\String\Rules;

class SuiteParser extends Parser
{
	/** @var Rule */
	private $rule;

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
subject: AND lineNumber subjectLabel optionalComments code
subjectLabel: RE // Test\s+
lineNumber: STRING
code: MANY codeBlock 1
codeBlock: AND codeUnit optionalComments
codeUnit: RE (?!(?:// (?:Test|Cause|Effect))|/\*|$).+?(?=(?:// (?:Test|Cause|Effect))|/\*|$)
cases: MANY case 1
case: AND optionalCause effect
optionalCause: MANY cause 0 1
cause: AND causeLabel optionalComments code
causeLabel: RE // Cause\s+
effect: AND lineNumber effectLabel optionalComments code
effectLabel: RE // Effect\s+
EOS;

		$rules = new Rules($this, $grammar);
		$this->rule = $rules->getRule('suite');
	}

	public function parse($input)
	{
		$this->input = $input;

		return $this->run($this->rule, $input);
	}

	public function getSuite(array $match)
	{
		$namespace = $match[1];
		$uses = $match[2];
		$tests = $match[3];

		return [
			'namespace' => $namespace,
			'uses' => $uses,
			'tests' => $tests
		];
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
		$output = [];

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

		return [
			$alias => $namespace
		];
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

		return [
			$line => [
				'code' => $code,
				'cases' => $cases
			]
		];
	}

	public function getLineNumber()
	{
		$position = $this->getPosition();
		$text = substr($this->input, 0, $position);
		return substr_count($text, "\n") + 1;
	}

	public function getSubject(array $matches)
	{
		$line = $matches[0];
		$code = $matches[3];

		return [$line, $code];
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
		$cause = $match[0];
		$line = $match[1][0];
		$effect = $match[1][1];

		$script = self::extractScript($effect);

		return [
			$line => [
				'cause' => $cause,
				'effect' => $effect,
				'script' => $script,
				'issues' => null,
				'coverage' => null
			]
		];
	}

	private static function extractScript(&$php)
	{
		$script = [];

		$lines = Re::split('\\r?\\n', $php);

		foreach ($lines as &$line) {
			if (self::extractFromCall($line, $action)) {
				$script[] = $action;
			}
		}

		$php = implode("\n", $lines);

		return $script;
	}

	private static function extractFromCall(&$php, &$action)
	{
		$expression = '^(?<php>.*?[a-zA-Z_0-9]+\\s*\\(.*?\\);)\\s*//\\s*(?<action>.*)$';

		if (!Re::match($expression, $php, $match)) {
			return false;
		}

		$php = $match['php'];
		$action = $match['action'];

		return true;
	}

	public function getOptionalCause(array $matches)
	{
		return array_shift($matches);
	}

	public function getCause(array $matches)
	{
		return $matches[2];
	}

	public function getEffect(array $matches)
	{
		$line = $matches[0];
		$code = $matches[3];

		return [$line, $code];
	}
}
