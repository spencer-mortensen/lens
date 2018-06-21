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

namespace Lens_0_0_56\Lens\Tests;

use Lens_0_0_56\Lens\Archivist\Comparer;
use Lens_0_0_56\Lens\Archivist\Archives\ObjectArchive;
use Lens_0_0_56\Lens\Php\Namespacing;
use Lens_0_0_56\Lens\Formatter;

class Analyzer
{
	/** @var Namespacing */
	private $namespacing;

	/** @var Comparer */
	private $comparer;

	/** @var array */
	private $script;

	/** @var Formatter */
	private $expectedFormatter;

	/** @var Formatter */
	private $actualFormatter;

	public function __construct(Namespacing $namespacing, Comparer $comparer)
	{
		$this->namespacing = $namespacing;
		$this->comparer = $comparer;
	}

	public function analyze(array $script, array $expectedPost = null, array $actualPre = null, array $actualPost = null)
	{
		if ($this->isFixtureFailure($actualPre)) {
			return $this->getFixtureIssues($actualPre);
		}

		return $this->getDifferenceIssues($script, $expectedPost, $actualPost);
	}

	private function isFixtureFailure(array $state)
	{
		return (0 < count($state['errors'])) || ($state['exception'] !== null);
	}

	private function getFixtureIssues(array $actual)
	{
		$expected = [
			'errors' => [],
			'exception' => null
		];

		$formatter = $this->getFormatter([]);

		$this->expectedFormatter = $formatter;
		$this->actualFormatter = $formatter;

		return array_merge(
			$this->getValueIssues('exception', $expected, $actual),
			$this->getArrayIssues('errors', $expected, $actual)
		);
	}

	private function getDifferenceIssues(array $script, array $expected, array $actual)
	{
		// TODO: use ALL of the variables (including the variables from the "\\ Cause" and "\\ Effect" sections)
		$expectedVariables = $expected['variables'];
		$actualVariables = $actual['variables'];

		$this->expectedFormatter = $this->getFormatter($expectedVariables);
		$this->actualFormatter = $this->getFormatter($actualVariables);
		$this->script = $script;

		return array_merge(
			$this->getArrayIssues('calls', $expected, $actual),
			$this->getMutualMapIssues('variables', $expected, $actual),
			$this->getMapIssues('globals', $expected, $actual),
			$this->getMapIssues('constants', $expected, $actual),
			$this->getValueIssues('output', $expected, $actual),
			$this->getArrayIssues('errors', $expected, $actual),
			$this->getValueIssues('exception', $expected, $actual)
		);
	}

	private function getFormatter(array $variables)
	{
		$objectNames = self::getObjectNames($variables);
		return new Formatter($this->namespacing, $objectNames);
	}

	private static function getObjectNames(array $variables)
	{
		$names = [];

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

	private function getArrayIssues($name, array $expected, array $actual)
	{
		$expectedValues = $expected[$name];
		$actualValues = $actual[$name];

		$n = max(count($expectedValues), count($actualValues));

		if ($n === 0) {
			return [];
		}

		$keys = range(0, $n - 1, 1);

		return $this->getIssues($name, $keys, $expectedValues, $actualValues);
	}

	private function getMapIssues($name, array $expected, array $actual)
	{
		$expectedValues = $expected[$name];
		$actualValues = $actual[$name];

		$keys = array_keys($expectedValues + $actualValues);

		return $this->getIssues($name, $keys, $expectedValues, $actualValues);
	}

	private function getMutualMapIssues($name, array $expected, array $actual)
	{
		$expectedValues = $expected[$name];
		$actualValues = $actual[$name];

		$keys = array_keys(array_intersect_key($expectedValues, $actualValues));

		return $this->getIssues($name, $keys, $expectedValues, $actualValues);
	}

	private function getIssues($name, array $keys, array $expectedValues, array $actualValues)
	{
		$formatterName = ucfirst($name);
		$expectedFormatter = [$this, "expected{$formatterName}Formatter"];
		$actualFormatter = [$this, "actual{$formatterName}Formatter"];

		$issues = [];

		foreach ($keys as $key) {
			if (array_key_exists($key, $expectedValues) && array_key_exists($key, $actualValues) && $this->comparer->isSame($expectedValues[$key], $actualValues[$key])) {
				$issue = $this->getIssue($key, $expectedValues, $expectedFormatter);
			} else {
				$issue = [
					'expected' => $this->getIssue($key, $expectedValues, $expectedFormatter),
					'actual' => $this->getIssue($key, $actualValues, $actualFormatter)
				];
			}

			$issues[] = $issue;
		}

		return $issues;
	}

	private function getIssue($key, array $values, $formatter)
	{
		if (!array_key_exists($key, $values)) {
			return null;
		}

		return call_user_func($formatter, $key, $values[$key]);
	}

	private function getValueIssues($name, array $expected, array $actual)
	{
		$expectedValue = $expected[$name];
		$actualValue = $actual[$name];

		if (($expectedValue === null) && ($actualValue === null)) {
			return [];
		}

		$formatterName = ucfirst($name);
		$expectedFormatter = [$this, "expected{$formatterName}Formatter"];
		$actualFormatter = [$this, "actual{$formatterName}Formatter"];

		if ($this->comparer->isSame($expectedValue, $actualValue)) {
			$issue = call_user_func($expectedFormatter, $expectedValue);
		} else {
			$issue = [
				'expected' => call_user_func($expectedFormatter, $expectedValue),
				'actual' => call_user_func($actualFormatter, $actualValue)
			];
		}

		return [$issue];
	}

	protected function expectedCallsFormatter($i, $call)
	{
		return $this->getCall($this->expectedFormatter, $call, $this->script, $i);
	}

	protected function actualCallsFormatter($i, $call)
	{
		return $this->getCall($this->actualFormatter, $call, $this->script, $i);
	}

	private function getCall(Formatter $formatter, $call, array $script, $i)
	{
		$action = &$script[$i];

		return $formatter->getCall($call, $action);
	}

	protected function expectedVariablesFormatter($name, $value)
	{
		return $this->expectedFormatter->getVariable($name, $value);
	}

	protected function actualVariablesFormatter($name, $value)
	{
		return $this->actualFormatter->getVariable($name, $value);
	}

	protected function expectedGlobalsFormatter($name, $value)
	{
		return $this->expectedFormatter->getGlobal($name, $value);
	}

	protected function actualGlobalsFormatter($name, $value)
	{
		return $this->actualFormatter->getGlobal($name, $value);
	}

	protected function expectedConstantsFormatter($name, $value)
	{
		return $this->expectedFormatter->getConstant($name, $value);
	}

	protected function actualConstantsFormatter($name, $value)
	{
		return $this->actualFormatter->getConstant($name, $value);
	}

	protected function expectedOutputFormatter($output)
	{
		return $this->getOutput($this->expectedFormatter, $output);
	}

	protected function actualOutputFormatter($output)
	{
		return $this->getOutput($this->actualFormatter, $output);
	}

	private function getOutput(Formatter $formatter, $output)
	{
		if ($output === null) {
			return null;
		}

		return $formatter->getOutput($output);
	}

	protected function expectedErrorsFormatter($i, array $error)
	{
		return $this->expectedFormatter->getError($error);
	}

	protected function actualErrorsFormatter($i, array $error)
	{
		return $this->actualFormatter->getError($error);
	}

	protected function expectedExceptionFormatter(ObjectArchive $exception = null)
	{
		return $this->getException($this->expectedFormatter, $exception);
	}

	protected function actualExceptionFormatter(ObjectArchive $exception = null)
	{
		return $this->getException($this->actualFormatter, $exception);
	}

	private function getException(Formatter $formatter, ObjectArchive $exception = null)
	{
		if ($exception === null) {
			return null;
		}

		return $formatter->getException($exception);
	}
}
