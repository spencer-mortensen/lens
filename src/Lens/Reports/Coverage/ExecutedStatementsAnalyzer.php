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

namespace Lens_0_0_56\Lens\Reports\Coverage;

class ExecutedStatementsAnalyzer
{
	public function getExecutedStatements(array $executableStatements, array $project)
	{
		$map = $this->getCoverageMap($executableStatements);

		foreach ($project['suites'] as $suiteFile => $suite) {
			foreach ($suite['tests'] as $testLine => $test) {
				foreach ($test['cases'] as $caseLine => $case) {
					$this->applyCoverage($map, $case['coverage']);
				}
			}
		}

		return $map;
	}

	private function getCoverageMap(array $input)
	{
		$output = [];

		$this->mapTypeCoverage('classes', $input, $output);
		$this->mapTypeCoverage('functions', $input, $output);
		$this->mapTypeCoverage('traits', $input, $output);

		return $output;
	}

	private function mapTypeCoverage($type, array $input, array &$output)
	{
		foreach ($input[$type] as $name => $lines) {
			$output[$type][$name] = $this->mapLineFalse($lines);
		}
	}

	private function mapLineFalse(array $lines)
	{
		$output = [];

		foreach ($lines as $line) {
			$output[$line] = false;
		}

		return $output;
	}

	private function applyCoverage(array &$map, array $coverage = null)
	{
		if ($coverage === null) {
			return;
		}

		$this->applyTypeCoverage('classes', $map, $coverage);
		$this->applyTypeCoverage('functions', $map, $coverage);
		$this->applyTypeCoverage('traits', $map, $coverage);
	}

	private function applyTypeCoverage($type, array &$map, array $coverage)
	{
		foreach ($coverage[$type] as $name => $lines) {
			if (isset($map[$type][$name])) {
				$this->updateMap($map[$type][$name], $lines);
			}
		}
	}

	private function updateMap(array &$map, array $lines)
	{
		foreach ($lines as $line) {
			if (isset($map[$line])) {
				$map[$line] = true;
			}
		}
	}
}
