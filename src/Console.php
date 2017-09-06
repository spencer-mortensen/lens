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

use Lens\Archivist\Archives\ObjectArchive;
use Lens\Archivist\Comparer;

class Console
{
	private static $maximumLineLength = 96;

	/** @var Comparer */
	private $comparer;

	/** @var integer */
	private $passedTestsCount;

	/** @var array */
	private $failedTests;

	public function __construct()
	{
		$this->comparer = new Comparer();
		$this->passedTestsCount = 0;
		$this->failedTests = array();
	}

	public function summarize(array $suites)
	{
		foreach ($suites as $filePath => $suite) {
			$tests = $suite['tests'];

			foreach ($tests as $lineTest => $test) {
				$subject = $test['subject'];
				$cases = $test['cases'];

				foreach ($cases as $lineCase => $case) {
					$input = $case['input'];
					$output = $case['output'];
					$results = $case['results'];

					$this->verify($subject, $input, $output, $results);
				}
			}
		}

		$output = array();

		if (0 < $this->passedTestsCount) {
			$output[] = $this->showPassedTests();
		}

		if (0 < count($this->failedTests)) {
			$output[] = $this->showFailedTests();
		}

		return implode("\n", $output) . "\n";
	}

	private function verify($subject, $input, $output, array $results)
	{
		$fixture = $results['fixture'];
		$expected = $results['expected'];
		$actual = $results['actual'];

		if (!is_array($expected) || !is_array($actual)) {
			$fixtureFormatter = new Formatter(self::getObjectNames($fixture));
			$issues = $this->getFixtureIssues($fixtureFormatter, $fixture);
			$this->failedTests[] = $this->getFailedTestText($subject, $input, $output, $issues);
			return false;
		}

		self::ignoreSetupVariables($expected, $actual);
		$this->ignoreMutualValues($expected, $actual);

		if (self::isEmptyState($expected) && self::isEmptyState($actual)) {
			++$this->passedTestsCount;
			return true;
		}

		$issues = $this->getDifferenceIssues($expected, $actual);
		$this->failedTests[] = $this->getFailedTestText($subject, $input, $output, $issues);
		return false;
	}

	private static function getObjectNames(array $state = null)
	{
		$names = array();

		if (!is_array($state)) {
			return $names;
		}

		/** @var ObjectArchive $value */
		foreach ($state['variables'] as $name => $value) {
			if (!is_object($value) || $value->isResourceArchive()) {
				continue;
			}

			$id = $value->getId();
			$names[$id] = $name;
		}

		return $names;
	}

	private static function isEmptyState($state)
	{
		foreach ($state as $key => $value) {
			if (0 < count($value)) {
				return false;
			}
		}

		return true;
	}

	private static function ignoreSetupVariables(array $expected, array &$actual)
	{
		foreach ($actual['variables'] as $name => $value) {
			if (!array_key_exists($name, $expected['variables'])) {
				unset($actual['variables'][$name]);
			}
		}
	}

	private function ignoreMutualValues(array &$expected, array &$actual)
	{
		$this->removeMutualValue($expected['output'], $actual['output']);
		$this->removeMutualArrayValues($expected['variables'], $actual['variables']);
		$this->removeMutualArrayValues($expected['globals'], $actual['globals']);
		$this->removeMutualArrayValues($expected['constants'], $actual['constants']);
		$this->removeMutualValue($expected['exception'], $actual['exception']);
		$this->removeMutualArrayValues($expected['errors'], $actual['errors']);
		$this->removeMutualArrayValues($expected['calls'], $actual['calls']);
	}

	private function removeMutualValue(&$a, &$b)
	{
		if ($this->comparer->isSame($a, $b)) {
			$a = $b = null;
		}
	}

	private function removeMutualArrayValues(array &$a, array &$b)
	{
		$mutualKeys = array_intersect_key($a, $b);

		foreach ($mutualKeys as $key => $value) {
			if ($this->comparer->isSame($a[$key], $b[$key])) {
				unset($a[$key], $b[$key]);
			}
		}
	}

	private function getFixtureIssues(Formatter $fixtureFormatter, array $fixture)
	{
		$sections = array(
			self::getUnexpectedMessages($this->getExceptionMessages($fixtureFormatter, $fixture['exception'])),
			self::getUnexpectedMessages($this->getErrorMessages($fixtureFormatter, $fixture['errors']))
		);

		// TODO: show a troubleshooting message when the $issuesText is empty
		return implode("\n", call_user_func_array('array_merge', $sections));
	}

	private function getDifferenceIssues(array $expected, array $actual)
	{
		$expectedFormatter = new Formatter(self::getObjectNames($expected));
		$actualFormatter = new Formatter(self::getObjectNames($actual));

		$sections = array(
			self::getUnexpectedMessages($this->getExceptionMessages($actualFormatter, $actual['exception'])),
			self::getMissingMessages($this->getExceptionMessages($expectedFormatter, $expected['exception'])),

			self::getUnexpectedMessages($this->getErrorMessages($actualFormatter, $actual['errors'])),
			self::getMissingMessages($this->getErrorMessages($expectedFormatter, $expected['errors'])),

			self::getUnexpectedMessages($this->getVariableMessages($actualFormatter, $actual['variables'])),
			self::getMissingMessages($this->getVariableMessages($expectedFormatter, $expected['variables'])),

			self::getUnexpectedMessages($this->getCallMessages($actualFormatter, $actual['calls'])),
			self::getMissingMessages($this->getCallMessages($expectedFormatter, $expected['calls'])),

			self::getUnexpectedMessages($this->getGlobalMessages($actualFormatter, $actual['globals'])),
			self::getMissingMessages($this->getGlobalMessages($expectedFormatter, $expected['globals'])),

			self::getUnexpectedMessages($this->getConstantMessages($actualFormatter, $actual['constants'])),
			self::getMissingMessages($this->getConstantMessages($expectedFormatter, $expected['constants'])),

			self::getUnexpectedMessages($this->getOutputMessages($actualFormatter, $actual['output'])),
			self::getMissingMessages($this->getOutputMessages($expectedFormatter, $expected['output']))
		);

		// TODO: show a troubleshooting message when the $issuesText is empty
		return implode("\n", call_user_func_array('array_merge', $sections));
	}

	private function getUnexpectedMessages(array $messages)
	{
		return array_map(array($this, 'plus'), $messages);
	}

	private function plus($message)
	{
		return ' + ' . $message;
	}

	private function getMissingMessages(array $messages)
	{
		return array_map(array($this, 'minus'), $messages);
	}

	private function minus($message)
	{
		return ' - ' . $message;
	}

	private function getOutputMessages(Formatter $formatter, $output)
	{
		if ($output === null) {
			return array();
		}

		return array($formatter->getOutput($output));
	}

	private function getVariableMessages(Formatter $formatter, array $variables)
	{
		$messages = array();

		foreach ($variables as $name => $value) {
			$messages[] = $formatter->getVariable($name, $value);
		}

		return $messages;
	}

	private function getGlobalMessages(Formatter $formatter, array $globals)
	{
		$messages = array();

		foreach ($globals as $name => $value) {
			$messages[] = $formatter->getGlobal($name, $value);
		}

		return $messages;
	}

	private function getConstantMessages(Formatter $formatter, array $constants)
	{
		$messages = array();

		foreach ($constants as $name => $value) {
			$messages[] = $formatter->getConstant($name, $value);
		}

		return $messages;
	}

	private function getExceptionMessages(Formatter $formatter, ObjectArchive $exception = null)
	{
		if ($exception === null) {
			return array();
		}

		return array($formatter->getException($exception));
	}

	private function getErrorMessages(Formatter $formatter, array $errors)
	{
		return array_map(array($formatter, 'getError'), $errors);
	}

	private function getCallMessages(Formatter $formatter, array $calls)
	{
		return array_map(array($formatter, 'getCall'), $calls);
	}

	private function getFailedTestText($subject, $input, $output, $issues)
	{
		$sections = array();
		$sections[] = "   // Test\n" . self::pad(self::wrap($subject), '   ');

		if ($input !== null) {
			$sections[] = "   // Input\n" . self::pad(self::wrap($input), '   ');
		}

		$sections[] = "   // Output\n" . self::pad(self::wrap($output), '   ');
		$sections[] = "   // Issues\n" . $issues;

		return implode("\n\n", $sections);
	}

	private function showPassedTests()
	{
		return "Passed tests: {$this->passedTestsCount}";
	}

	private function showFailedTests()
	{
		$count = count($this->failedTests);

		return "Failed tests: {$count}\n\n\n" . implode("\n\n\n", $this->failedTests);
	}

	// TODO: this is duplicated elsewhere
	private static function wrap($string)
	{
		return wordwrap($string, self::$maximumLineLength, "\n", true);
	}

	// TODO: this is duplicated elsewhere
	private static function pad($string, $prefix)
	{
		$pattern = self::getPattern('^(.+)$', 'm');
		$replacement = preg_quote($prefix) . '$1';

		return preg_replace($pattern, $replacement, $string);
	}

	private static function getPattern($expression, $flags = null)
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}
}
