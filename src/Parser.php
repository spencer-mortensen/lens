<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

/*
// EBNF:
file = php_tag, fixture, tests;
fixture = code;
tests = test, { test };
test = subject, case, { case };
case = [ input ], output;
subject = subject_label, code;
input = input_label, code;
output = output_label, code;
*/

class Parser
{
	/** @var string */
	private $input;

	// TODO: add descriptive error messages when a test file is malformed
	public function parse($input, &$fixture, &$tests)
	{
		$this->input = $input;
		$this->stripComments();

		return $this->getSuite($fixture, $tests);
	}

	private function stripComments()
	{
		$this->strip("/\\*.*?\\*/\n*");
	}

	private function getSuite(&$fixture, &$tests)
	{
		return $this->getPhpTag()
			&& $this->getCode($fixture)
			&& $this->getTests($tests);
	}

	private function getPhpTag()
	{
		return $this->read("<\\?php\n+");
	}

	private function getCode(&$code)
	{
		// TODO: improve this regular expression (use the lookahead more judiciously)
		if ($this->read("(?!// Test|// Input|// Output).*?(?=// Test|// Input|// Output|$)", $matches)) {
			$code = trim($matches[0]);
		}

		return true;
	}

	private function getTests(&$tests)
	{
		$tests = array();

		while ($this->getTest($subject, $cases)) {
			$tests[] = array(
				'subject' => $subject,
				'cases' => $cases
			);
		}

		return 0 < count($tests);
	}

	private function getTest(&$subject, &$cases)
	{
		return $this->getSection('Test', $subject)
			&& $this->getCases($cases);
	}

	private function getSection($label, &$code)
	{
		return $this->getLabel($label)
			&& $this->getCode($code);
	}

	private function getCases(&$cases)
	{
		$cases = array();

		while ($this->getCase($input, $output)) {
			$cases[] = array(
				'input' => $input,
				'output' => $output
			);
		}

		return 0 < count($cases);
	}

	private function getCase(&$input, &$output)
	{
		$this->getSection('Input', $input);

		return $this->getSection('Output', $output);
	}

	private function getLabel($label)
	{
		return $this->read("// {$label}(?:\n+|\$)");
	}

	private function read($expression, &$output = null)
	{
		$pattern = self::getPattern($expression, 'As');

		if (preg_match($pattern, $this->input, $matches) !== 1) {
			return false;
		}

		$output = $matches;
		$length = strlen($matches[0]);
		$this->input = (string)substr($this->input, $length);

		return true;
	}

	private function strip($expression)
	{
		$pattern = self::getPattern($expression, 's');

		$this->input = preg_replace($pattern, '', $this->input, -1, $count);
	}

	private static function getPattern($expression, $flags)
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}
}
