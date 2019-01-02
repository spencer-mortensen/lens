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

namespace _Lens\Lens\Phases\Analysis\Tests\Parser;

use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class FileTokensParserStub
{
	public $rules = <<<'EOS'
suite: and preamble maybeMocks tests
preamble: get PREAMBLE
maybeMocks: any mocks 0 - 1
mocks: get MOCKS
tests: any test 0+
test: and subject cases
subject: get SUBJECT
cases: any testCase 0+
testCase: and maybeCause effect
maybeCause: any cause 0 - 1
cause: get CAUSE
effect: get EFFECT
EOS;

	public $startRule = 'suite';

	public function suite($preamble, $mocks, array $tests)
	{
		return [
			'preamble' => $preamble,
			'mocks' => $mocks,
			'tests' => $tests
		];
	}

	public function preamble(array $token)
	{
		return current($token);
	}

	public function maybeMocks(array $mocks)
	{
		return array_shift($mocks);
	}

	public function mocks(array $token)
	{
		return current($token);
	}

	public function tests(array $caseValues)
	{
		$output = [];

		foreach ($caseValues as $caseValue) {
			list($line, $test) = $caseValue;

			$output[$line] = $test;
		}

		return $output;
	}

	public function test($subject, array $cases)
	{
		return [
			$subject['origin'][1],
			[
				'subject' => $subject,
				'cases' => $cases
			]
		];
	}

	public function subject(array $token)
	{
		return current($token);
	}

	public function cases(array $caseValues)
	{
		$output = [];

		foreach ($caseValues as $caseValue) {
			list($line, $case) = $caseValue;

			$output[$line] = $case;
		}

		return $output;
	}

	public function testCase($cause, array $effect)
	{
		if ($cause === null) {
			$line = $effect['origin'][1];
		} else {
			$line = $cause['origin'][1];
		}

		return [
			$line,
			[
				'cause' => $cause,
				'effect' => $effect,
			]
		];
	}

	public function maybeCause(array $causes)
	{
		return array_shift($causes);
	}

	public function cause(array $token)
	{
		return current($token);
	}

	public function effect(array $token)
	{
		return current($token);
	}

	public function __invoke(TokenInput $input)
	{
	}

	public function __get($type)
	{
		switch ($type) {
			default: // case 'PREAMBLE':
				return 'FileLexer::PREAMBLE';

			case 'MOCKS':
				return 'FileLexer::MOCKS';

			case 'SUBJECT':
				return 'FileLexer::SUBJECT';

			case 'CAUSE';
				return 'FileLexer::CAUSE';

			case 'EFFECT':
				return 'FileLexer::EFFECT';
		}
	}
}
