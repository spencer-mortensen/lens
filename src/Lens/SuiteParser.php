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

use SpencerMortensen\Parser\Parser;

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
		$syntax = <<<'EOS'
suite: AND phpTag space code tests optionalSpace
phpTag: STRING <?php
space: RE \s+
code: AND comments codeGroups
comments: REPEAT comment 0
comment: RE /\*.*?\*/
codeGroups: REPEAT codeGroup 1
codeGroup: AND codeSection comments
codeSection: RE (?!// (?:Test|Input|Output)|/\*).+?(?=(?:// (?:Test|Input|Output)|/\*|$))
tests: REPEAT test 1
test: AND subject cases
subject: AND subjectLabel code
subjectLabel: STRING // Test
cases: REPEAT case 1
case: AND optionalInput output
optionalInput: REPEAT input 0 1
input: AND inputLabel code
inputLabel: STRING // Input
output: AND outputLabel code
outputLabel: STRING // Output
optionalSpace: RE \s*
EOS;

		parent::__construct($syntax);
	}

	public function parse($input)
	{
		return $this->evaluate($input, 'suite');
	}

	protected function formatSuite(array $values)
	{
		return array(
			'fixture' => $values[2],
			'tests' => $values[3]
		);
	}

	protected function formatCode(array $values)
	{
		return $values[1];
	}

	protected function formatCodeGroups(array $values)
	{
		return implode(PHP_EOL, $values);
	}

	protected function formatCodeGroup(array $values)
	{
		return trim($values[0]);
	}

	protected function formatTests(array $values)
	{
		return $values;
	}

	protected function formatTest(array $values)
	{
		return array(
			'subject' => $values[0],
			'cases' => $values[1]
		);
	}

	protected function formatSubject(array $values)
	{
		return trim($values[1]);
	}

	protected function formatCases(array $values)
	{
		return $values;
	}

	protected function formatCase(array $values)
	{
		return array(
			'input' => $values[0],
			'output' => $values[1]
		);
	}

	protected function formatOptionalInput(array $values)
	{
		if (count($values) === 0) {
			return null;
		}

		return $values[0];
	}

	protected function formatInput(array $values)
	{
		return trim($values[1]);
	}

	protected function formatOutput(array $values)
	{
		return trim($values[1]);
	}
}
