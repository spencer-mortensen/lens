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

namespace Lens_0_0_56\Lens\Reports;

use Lens_0_0_56\Lens\Archivist\Archives\ObjectArchive;
use Lens_0_0_56\Lens\Archivist\Comparer;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Formatter;
use Lens_0_0_56\Lens\Paragraph;

class TextReport implements Report
{
	/** @var string */
	private $autoload;

	/** @var Comparer */
	private $comparer;

	/** @var integer */
	private $passedTestsCount;

	/** @var integer */
	private $failedTestsCount;

	/** @var null|array */
	private $failedTest;

	public function __construct($autoload)
	{
		$this->autoload = $autoload;
		$this->comparer = new Comparer();
		$this->passedTestsCount = 0;
		$this->failedTestsCount = 0;
		$this->failedTest = null;
	}

	public function getReport(array $project)
	{
		foreach ($project['suites'] as $testsFile => $suite) {
			$namespace = $suite['namespace'];
			$uses = $suite['uses'];

			foreach ($suite['tests'] as $testLine => $test) {
				$testText = $test['code'];

				foreach ($test['cases'] as $caseLine => $case) {
					$this->summarizeCase($namespace, $uses, $testsFile, $testLine, $testText, $caseLine, $case);
				}
			}
		}

		$output = array(
			$this->showSummary()
		);

		if ($this->failedTest !== null) {
			$output[] = $this->failedTest;
		}

		return implode("\n\n", $output);
	}

	private function summarizeCase($namespace, array $uses, $testsFile, $testLine, $testText, $caseLine, array $case)
	{
		$script = $case['script'];
		$results = $case['results'];

		if ($case['summary']['pass']) {
			// TODO: use the counts from the $project array (rather than recompute them)
			++$this->passedTestsCount;
			return;
		}

		$caseText = $this->getCaseText($namespace, $uses, $testText, $case['input']);

		$actual = $results['actual'];
		$expected = $results['expected'];

		if (!is_array($expected['diff'])) {
			$issues = $this->getFixtureIssues($namespace, $uses, $expected['pre']);
		} elseif (!is_array($actual['diff'])) {
			$issues = $this->getFixtureIssues($namespace, $uses, $actual['pre']);
		} else {
			$issues = $this->getDifferenceIssues($namespace, $uses, $actual, $expected, $script);
		}

		if ($this->failedTest === null) {
			$this->failedTest = $this->getFailedTestText($caseText, $issues, $testsFile, $testLine, $caseLine);
		}

		++$this->failedTestsCount;
	}

	private function getCaseText($namespace, array $uses, $testText, $inputText)
	{
		$contextPhp = Code::getContextPhp($namespace, $uses);
		$requirePhp = $this->getRequireAutoloadPhp();

		$sections = array(
			$contextPhp,
			$requirePhp,
			$this->getSectionText('// Input', $inputText),
			$this->getSectionText('// Test', $testText)
		);

		$sections = array_filter($sections, 'is_string');

		return implode("\n\n", $sections);
	}

	private function getRequireAutoloadPhp()
	{
		if ($this->autoload === null) {
			return null;
		}

		$valuePhp = Code::getValuePhp($this->autoload);
		return Code::getRequirePhp($valuePhp);
	}

	private function getSectionText($label, $code)
	{
		if ($code === null) {
			return null;
		}

		return "{$label}\n{$code}";
	}

	private function getFixtureIssues($namespace, array $uses, array $state)
	{
		$formatter = self::getFormatter($namespace, $uses, $state['variables']);

		$sections = array(
			self::getUnexpectedMessages($this->getExceptionMessages($formatter, $state['exception'])),
			self::getUnexpectedMessages($this->getErrorMessages($formatter, $state['errors']))
		);

		// TODO: show a troubleshooting message when the $issuesText is empty
		return implode("\n", call_user_func_array('array_merge', $sections));
	}

	private function getDifferenceIssues($namespace, array $uses, array $actual, array $expected, array $script)
	{
		$actualDiff = $actual['diff'];
		$expectedDiff = $expected['diff'];

		// TODO: use ALL of the variables (including the variables from the "\\ Output" section)
		$actualVariables = array_merge($actual['pre']['variables'], $actual['post']['variables']);
		$expectedVariables = array_merge($expected['pre']['variables'], $expected['post']['variables']);

		$actualFormatter = self::getFormatter($namespace, $uses, $actualVariables);
		$expectedFormatter = self::getFormatter($namespace, $uses, $expectedVariables);

		// TODO: display ALL side-effects (NOT just the differences)
		$sections = array(
			self::getCallsMessages($actualFormatter, $expectedFormatter, $actual['post']['calls'], $expected['post']['calls'], $script),

			self::getMissingMessages($this->getVariableMessages($expectedFormatter, $expectedDiff['variables'])),
			self::getUnexpectedMessages($this->getVariableMessages($actualFormatter, $actualDiff['variables'])),

			self::getMissingMessages($this->getGlobalMessages($expectedFormatter, $expectedDiff['globals'])),
			self::getUnexpectedMessages($this->getGlobalMessages($actualFormatter, $actualDiff['globals'])),

			self::getMissingMessages($this->getConstantMessages($expectedFormatter, $expectedDiff['constants'])),
			self::getUnexpectedMessages($this->getConstantMessages($actualFormatter, $actualDiff['constants'])),

			self::getMissingMessages($this->getOutputMessages($expectedFormatter, $expectedDiff['output'])),
			self::getUnexpectedMessages($this->getOutputMessages($actualFormatter, $actualDiff['output'])),

			self::getMissingMessages($this->getErrorMessages($expectedFormatter, $expectedDiff['errors'])),
			self::getUnexpectedMessages($this->getErrorMessages($actualFormatter, $actualDiff['errors'])),

			self::getMissingMessages($this->getExceptionMessages($expectedFormatter, $expectedDiff['exception'])),
			self::getUnexpectedMessages($this->getExceptionMessages($actualFormatter, $actualDiff['exception'])),
		);

		return implode("\n", call_user_func_array('array_merge', $sections));
	}

	private static function getCallsMessages(Formatter $actualFormatter, Formatter $expectedFormatter, array $actualCalls, array $expectedCalls, array $script)
	{
		$output = array();

		$comparer = new Comparer();

		$n = max(count($actualCalls), count($expectedCalls));

		for ($i = 0; $i < $n; ++$i) {
			$expected = &$expectedCalls[$i];
			$actual = &$actualCalls[$i];
			$action = &$script[$i];

			$isSame = $comparer->isSame($expected, $actual);

			if ($isSame) {
				$output[] = self::equal($expectedFormatter->getCall($expected, $action));
			} else {
				if ($expected !== null) {
					$output[] = self::minus($expectedFormatter->getCall($expected, $action));
				}

				if ($actual !== null) {
					$output[] = self::plus($actualFormatter->getCall($actual, $action));
				}
			}
		}

		return $output;
	}

	private function getUnexpectedMessages(array $messages)
	{
		return array_map(array(__CLASS__, 'plus'), $messages);
	}

	private function getMissingMessages(array $messages)
	{
		return array_map(array(__CLASS__, 'minus'), $messages);
	}

	private static function minus($message)
	{
		return ' - ' . $message;
	}

	private static function equal($message)
	{
		return '   ' . $message;
	}

	private static function plus($message)
	{
		return ' + ' . $message;
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

	private function getFailedTestText($caseText, $issues, $testsFile, $testLine, $caseLine)
	{
		$caseText = Paragraph::wrap($caseText);
		$caseText = Paragraph::indent($caseText, '   ');

		$sections = array(
			"{$testsFile} (Line {$caseLine}):",
			$caseText,
			"   // Output\n" . $issues
		);

		return implode("\n\n", $sections);
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

	private static function getFormatter($namespace, array $uses, array $variables)
	{
		$objectNames = self::getObjectNames($variables);
		return new Formatter($namespace, $uses, $objectNames);
	}

	private static function getObjectNames(array $variables)
	{
		$names = array();

		foreach ($variables as $name => $value) {
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
