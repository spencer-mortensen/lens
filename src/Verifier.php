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

use Lens\Archivist\Comparer;

class Verifier
{
	public function __construct()
	{
		$this->comparer = new Comparer();
	}

	public function verify(array &$suites)
	{
		foreach ($suites as &$suite) {
			foreach ($suite['tests'] as &$test) {
				foreach ($test['cases'] as &$case) {
					$this->verifyCase($case);
				}
			}
		}
	}

	private function verifyCase(array &$case)
	{
		$results = &$case['results'];

		$results['pass'] = $this->isPassing($results['expected'], $results['actual']);
	}

	private function isPassing(array &$expectedResults, array &$actualResults)
	{
		$expected = &$expectedResults['diff'];
		$actual = &$actualResults['diff'];

		if (!is_array($expectedResults['post']) || !is_array($actualResults['post'])) {
			return false;
		}

		$expected = $expectedResults['post'];
		$actual = $actualResults['post'];

		$this->removeSetupVariables($expected, $actual);
		$this->removeMutualValues($expected, $actual);

		return self::isEmptyState($expectedResults['diff']) && self::isEmptyState($actualResults['diff']);
	}

	private function removeSetupVariables(array $expected, array &$actual)
	{
		foreach ($actual['variables'] as $name => $value) {
			if (!array_key_exists($name, $expected['variables'])) {
				unset($actual['variables'][$name]);
			}
		}
	}

	private function removeMutualValues(array &$expected, array &$actual)
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

	private static function isEmptyState($state)
	{
		foreach ($state as $key => $value) {
			if (0 < count($value)) {
				return false;
			}
		}

		return true;
	}
}
