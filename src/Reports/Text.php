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

namespace Lens\Reports;

use Lens\Archivist\Archives\ObjectArchive;
use Lens\Archivist\Comparer;
use Lens\Formatter;
use Lens\Paragraph;
use SpencerMortensen\RegularExpressions\Re;

class Text implements Report
{
	/** @var Comparer */
	private $comparer;

	/** @var integer */
	private $passedTestsCount;

	/** @var integer */
	private $failedTestsCount;

	/** @var array */
	private $failedTests;

	public function __construct()
	{
		$this->comparer = new Comparer();
		$this->passedTestsCount = 0;
		$this->failedTestsCount = 0;
		$this->failedTests = array();
	}

	public function getReport(array $project)
	{
		foreach ($project['suites'] as $testsFile => $suite) {
			foreach ($suite['tests'] as $testLine => $test) {
				$testText = $test['code'];

				foreach ($test['cases'] as $caseLine => $case) {
					$this->summarizeCase($testsFile, $testLine, $testText, $caseLine, $case);
				}
			}
		}

		$output = array();

		if (0 < $this->failedTestsCount) {
			$output[] = $this->showFailedTests();
		}

		$output[] = $this->showSummary();

		return implode("\n\n\n", $output);
	}

	private function summarizeCase($testsFile, $testLine, $testText, $caseLine, array $case)
	{
		$results = $case['results'];

		if ($case['summary']['pass']) {
			// TODO: use the counts from the $project array (rather than recompute them)
			++$this->passedTestsCount;
			return;
		}

		$caseText = $this->getCaseText($testText, $case['input']['text'], $case['output']['text']);

		$actual = $results['actual'];
		$expected = $results['expected'];

		if (!is_array($expected['diff'])) {
			$issues = $this->getFixtureIssues($expected['pre']);
		} elseif (!is_array($actual['diff'])) {
			$issues = $this->getFixtureIssues($actual['pre']);
		} else {
			$issues = $this->getDifferenceIssues($actual, $expected);
		}

		++$this->failedTestsCount;
		$this->failedTests[] = $this->getFailedTestText($caseText, $issues, $testsFile, $testLine, $caseLine);
	}

	private function getCaseText($testText, $inputText, $outputText)
	{
		$sections = array(
			$this->getSectionText('// Test', $testText),
			$this->getSectionText('// Input', $inputText),
			$this->getSectionText('// Output', $outputText)
		);

		$sections = array_filter($sections, 'is_string');

		return implode("\n\n", $sections);
	}

	private function getSectionText($label, $code)
	{
		if ($code === null) {
			return null;
		}

		return "{$label}\n{$code}";
	}

	private function getFixtureIssues(array $state)
	{
		$formatter = self::getFormatter($state);

		$sections = array(
			self::getUnexpectedMessages($this->getExceptionMessages($formatter, $state['exception'])),
			self::getUnexpectedMessages($this->getErrorMessages($formatter, $state['errors']))
		);

		// TODO: show a troubleshooting message when the $issuesText is empty
		return implode("\n", call_user_func_array('array_merge', $sections));
	}

	private function getDifferenceIssues(array $actual, array $expected)
	{
		$actualDiff = $actual['diff'];
		$expectedDiff = $expected['diff'];

		$actualFormatter = self::getFormatter($actual['pre']);
		$expectedFormatter = self::getFormatter($expected['pre']);

		$sections = array(
			self::getUnexpectedMessages($this->getExceptionMessages($actualFormatter, $actualDiff['exception'])),
			self::getMissingMessages($this->getExceptionMessages($expectedFormatter, $expectedDiff['exception'])),

			self::getUnexpectedMessages($this->getErrorMessages($actualFormatter, $actualDiff['errors'])),
			self::getMissingMessages($this->getErrorMessages($expectedFormatter, $expectedDiff['errors'])),

			self::getUnexpectedMessages($this->getCallMessages($actualFormatter, $actualDiff['calls'])),
			self::getMissingMessages($this->getCallMessages($expectedFormatter, $expectedDiff['calls'])),

			self::getUnexpectedMessages($this->getVariableMessages($actualFormatter, $actualDiff['variables'])),
			self::getMissingMessages($this->getVariableMessages($expectedFormatter, $expectedDiff['variables'])),

			self::getUnexpectedMessages($this->getGlobalMessages($actualFormatter, $actualDiff['globals'])),
			self::getMissingMessages($this->getGlobalMessages($expectedFormatter, $expectedDiff['globals'])),

			self::getUnexpectedMessages($this->getConstantMessages($actualFormatter, $actualDiff['constants'])),
			self::getMissingMessages($this->getConstantMessages($expectedFormatter, $expectedDiff['constants'])),

			self::getUnexpectedMessages($this->getOutputMessages($actualFormatter, $actualDiff['output'])),
			self::getMissingMessages($this->getOutputMessages($expectedFormatter, $expectedDiff['output']))
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

	private function getFailedTestText($caseText, $issues, $testsFile, $testLine, $caseLine)
	{
		$caseText = Paragraph::wrap($caseText);
		$caseText = Paragraph::indent($caseText, '   ');

		$sections = array(
			"{$testsFile} (Line {$caseLine}):",
			$caseText,
			"   // Issues\n" . $issues
		);

		return implode("\n\n", $sections);
	}

	private function showFailedTests()
	{
		return implode("\n\n\n", $this->failedTests);
	}

	private function showSummary()
	{
		$output = array();

		if (0 < $this->passedTestsCount) {
			$output[] = "Passed tests: {$this->passedTestsCount}";
		}

		if (0 < $this->failedTestsCount) {
			$output[] = "Failed tests: {$this->failedTestsCount}";
		}

		return implode("\n", $output);
	}

	private static function getFormatter(array $state)
	{
		$objectNames = self::getObjectNames($state);
		return new Formatter($objectNames);
	}

	private static function getObjectNames(array $state)
	{
		$names = array();

		foreach ($state['variables'] as $name => $value) {
			/** @var ObjectArchive $value */
			if (!is_object($value) || $value->isResourceArchive()) {
				continue;
			}

			$id = $value->getId();
			$names[$id] = $name;
		}

		return $names;
	}
}
