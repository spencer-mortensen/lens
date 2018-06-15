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

class TextReport
{
	/** @var CaseText */
	private $caseText;

	/** @var integer */
	private $passedTestsCount;

	/** @var integer */
	private $failedTestsCount;

	/** @var null|array */
	private $failedTest;

	public function __construct(CaseText $caseText)
	{
		$this->caseText = $caseText;
		$this->passedTestsCount = 0;
		$this->failedTestsCount = 0;
		$this->failedTest = null;
	}

	public function getReport(array $project)
	{
		foreach ($project['suites'] as $suiteFile => $suite) {
			$this->caseText->setSuite($suiteFile, $suite['namespace'], $suite['uses']);

			foreach ($suite['tests'] as $testLine => $test) {
				$this->caseText->setTest($test['code']);

				foreach ($test['cases'] as $caseLine => $case) {
					$this->caseText->setCase($caseLine, $case['input'], $case['issues']);

					$this->summarizeCase($case['issues']);
				}
			}
		}

		$output = [
			$this->showSummary()
		];

		if ($this->failedTest !== null) {
			$output[] = $this->failedTest;
		}

		return implode("\n\n", $output);
	}

	private function summarizeCase(array $issues)
	{
		if ($this->isPassing($issues)) {
			++$this->passedTestsCount;
			return;
		}

		if ($this->failedTest === null) {
			$this->failedTest = $this->caseText->getText();
		}

		++$this->failedTestsCount;
	}

	// TODO: this is duplicated elsewhere:
	private function isPassing(array $issues)
	{
		foreach ($issues as $issue) {
			if (is_array($issue)) {
				return false;
			}
		}

		return true;
	}

	private function showSummary()
	{
		$output = [];

		if (0 < $this->passedTestsCount) {
			$output[] = "Passed tests: {$this->passedTestsCount}";
		}

		if (0 < $this->failedTestsCount) {
			$output[] = "Failed tests: {$this->failedTestsCount}";
		}

		return implode("\n", $output);
	}
}
