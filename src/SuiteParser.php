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

use SpencerMortensen\Parser\ReadableRules;
use SpencerMortensen\Parser\String\Parser;

/*
suites: {
	<file>: <suite>
}

suite: {
	"fixture": "..."
	"tests": {
		<line>: <test>
	}
}

test: {
	"subject": "...",
	"cases": {
		<line>: <case>
	}
}

case: {
	"input": "...",
	"output": "...",
	"result": <result>
}

result: {
	"fixture": <state>,
	"expected": <state>|null,
	"actual": <state>|null
}

state: {...}
*/

class SuiteParser extends Parser
{
	public function __construct()
	{
		$grammar = <<<'EOS'
suite: AND phpTag code tests
phpTag: STRING <?php
code: AND codeUnit codeGroups
codeUnit: RE .*?(?=(?:\n// (?:Test|Input|Output))|/\*|$)\s?
codeGroups: MANY codeGroup 0
codeGroup: AND comment codeUnit
comment: RE /\*.*?\*/
tests: MANY test 1
test: AND subject cases
subject: AND subjectLabel code
subjectLabel: STRING // Test
cases: MANY case 1
case: AND optionalInput output
optionalInput: MANY input 0 1
input: AND inputLabel code
inputLabel:  STRING // Input
output: AND outputLabel code
outputLabel: STRING // Output
EOS;

		$rules = new ReadableRules($this, $grammar);
		$rule = $rules->getRule('suite');

		parent::__construct($rule);
	}

	public function getSuite(array $values)
	{
		return array(
			'fixture' => $values[1],
			'tests' => $values[2]
		);
	}

	public function getCode(array $values)
	{
		$values = array_filter($values, 'is_string');


		if (count($values) === 0) {
			return null;
		}

		return implode("\n", $values);
	}

	public function getCodeUnit(array $values)
	{
		$php = trim($values[0]);

		if (strlen($php) === 0) {
			return null;
		}

		return $php;
	}

	public function getCodeGroups(array $values)
	{
		$values = array_filter($values, 'is_string');

		if (count($values) === 0) {
			return null;
		}

		return implode("\n", $values);
	}

	public function getCodeGroup(array $values)
	{
		return $values[1];
	}

	public function getTest(array $values)
	{
		return array(
			'subject' => $values[0],
			'cases' => $values[1]
		);
	}

	public function getSubject(array $values)
	{
		return trim($values[1]);
	}

	public function getCase(array $values)
	{
		return array(
			'input' => $values[0],
			'output' => $values[1]
		);
	}

	public function getOptionalInput(array $values)
	{
		if (count($values) === 0) {
			return null;
		}

		return $values[0];
	}

	public function getInput(array $values)
	{
		return trim($values[1]);
	}

	public function getOutput(array $values)
	{
		return trim($values[1]);
	}
}
