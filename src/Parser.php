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
			&& $this->getFixture($fixture)
			&& $this->getTests($tests);
	}

	private function getPhpTag()
	{
		return $this->read("<\\?php\n+");
	}

	private function getFixture(&$fixture)
	{
		$this->getCode($fixture);

		$fixture = $this->useMockNamespace($fixture);

		return true;
	}

	private function getCode(&$code)
	{
		// TODO: improve this regular expression (use the lookahead more judiciously)
		if ($this->read("(?!// Test|// Input|// Output).*?(?=// Test|// Input|// Output|$)", $matches)) {
			$code = trim($matches[0]);
		}

		return true;
	}

	private function useMockNamespace($code)
	{
		$pattern = self::getPattern('^use\\s+([^;]+);\\s*// Mock$', 'm');

		return preg_replace($pattern, 'use TestPhp\\Mock\\\\$1;', $code);
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

	private function getLabel($label)
	{
		return $this->read("// {$label}(?:\n+|\$)");
	}

	private function getCases(&$cases)
	{
		$cases = array();

		while ($this->getCase($text, $input, $output)) {
			$cases[] = array(
				'text' => $text,
				'input' => $input,
				'output' => $output
			);
		}

		return 0 < count($cases);
	}

	private function getCase(&$text, &$input, &$output)
	{
		$this->getSection('Input', $input);

		if (!$this->getSection('Output', $output)) {
			return false;
		}

		$text = self::getText($input, $output);
		$output = $this->useMockCalls($output);

		return true;
	}

	private static function getText($input, $output)
	{
		$text = '';

		if ($input !== null) {
			$text .= "// Input\n{$input}\n\n";
		}

		$text .= "// Output\n{$output}";

		return $text;
	}

	private function useMockCalls($code)
	{
		$expression = '^\\s*(\\$.+?)->(.+?)\\((.*?)\\);\\s*// (return|throw)\\s+(.*);$';

		$pattern = self::getPattern($expression, 'm');

		return preg_replace_callback($pattern, 'self::getMockCall', $code);
	}

	protected static function getMockCall(array $match)
	{
		list(, $object, $method, $argumentList, $resultAction, $resultValue) = $match;

		$methodName = var_export($method, true);
		$callable = "array({$object}, {$methodName})";

		$arguments = "array({$argumentList})";

		$resultType = (integer)($resultAction === 'throw');
		$result = "array({$resultType}, {$resultValue})";

		return "\\TestPhp\\Agent::call({$callable}, {$arguments}, {$result});";
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
