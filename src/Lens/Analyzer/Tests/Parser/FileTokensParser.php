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

namespace _Lens\Lens\Analyzer\Tests\Parser;

use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class FileTokensParser
{
	/** @var TokenInput */
	private $input;

	/** @var mixed */
	private $output;

	/** @var array|null */
	private $expectation;

	/** @var mixed */
	private $position;

	public function parse(TokenInput $input)
	{
		$this->input = $input;
		$this->expectation = null;
		$this->position = null;

		if ($this->readSuite($output)) {
			$this->output = $output;
			$this->position = $this->input->getPosition();
			return true;
		}

		$this->output = null;
		return false;
	}

	public function getOutput()
	{
		return $this->output;
	}

	public function getExpectation()
	{
		return $this->expectation;
	}

	public function getPosition()
	{
		return $this->position;
	}

	private function readSuite(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readPreamble($preamble) && $this->readMaybeMocks($mocks) && $this->readTests($tests)) {
			$output = [
				'preamble' => $preamble,
				'mocks' => $mocks,
				'tests' => $tests
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readPreamble(&$output)
	{
		if ($this->input->read(FileLexer::PREAMBLE, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('preamble');
		return false;
	}

	private function readMaybeMocks(&$output)
	{
		$mocks = [];

		if ($this->readMocks($input)) {
			$mocks[] = $input;
		}

		$output = array_shift($mocks);

		return true;
	}

	private function readMocks(&$output)
	{
		if ($this->input->read(FileLexer::MOCKS, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('mocks');
		return false;
	}

	private function readTests(&$output)
	{
		$caseValues = [];

		while ($this->readTest($input)) {
			$caseValues[] = $input;
		}

		$output = [];

		foreach ($caseValues as $caseValue) {
			list($line, $test) = $caseValue;

			$output[$line] = $test;
		}

		return true;
	}

	private function readTest(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readSubject($subject) && $this->readCases($cases)) {
			$output = [
				$subject['origin'][1],
				[
					'subject' => $subject,
					'cases' => $cases
				]
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readSubject(&$output)
	{
		if ($this->input->read(FileLexer::SUBJECT, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('subject');
		return false;
	}

	private function readCases(&$output)
	{
		$caseValues = [];

		while ($this->readTestCase($input)) {
			$caseValues[] = $input;
		}

		$output = [];

		foreach ($caseValues as $caseValue) {
			list($line, $case) = $caseValue;

			$output[$line] = $case;
		}

		return true;
	}

	private function readTestCase(&$output)
	{
		$position = $this->input->getPosition();

		if ($this->readMaybeCause($cause) && $this->readEffect($effect)) {
			if ($cause === null) {
				$line = $effect['origin'][1];
			} else {
				$line = $cause['origin'][1];
			}

			$output = [
				$line,
				[
					'cause' => $cause,
					'effect' => $effect,
				]
			];
			return true;
		}

		$this->input->setPosition($position);
		return false;
	}

	private function readMaybeCause(&$output)
	{
		$causes = [];

		if ($this->readCause($input)) {
			$causes[] = $input;
		}

		$output = array_shift($causes);

		return true;
	}

	private function readCause(&$output)
	{
		if ($this->input->read(FileLexer::CAUSE, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('cause');
		return false;
	}

	private function readEffect(&$output)
	{
		if ($this->input->read(FileLexer::EFFECT, $token)) {
			$output = current($token);
			return true;
		}

		$this->setExpectation('effect');
		return false;
	}

	private function setExpectation($rule)
	{
		$this->expectation = $rule;
		$this->position = $this->input->getPosition();
	}
}
