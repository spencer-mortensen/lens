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

use Lens_0_0_56\Lens\Paragraph;
use Lens_0_0_56\Lens\Url;

class TapReport
{
	/** @var CaseText */
	private $caseText;

	public function __construct(CaseText $caseText)
	{
		$this->caseText = $caseText;
	}

	public function getReport(array $project, $isUpdateAvailable)
	{
		$cases = $this->getCases($project);

		$lines = [
			$this->getVersion(),
			$this->getPlan($cases)
		];

		$lines = array_merge($lines, $cases);

		if ($isUpdateAvailable) {
			$lines[] = '# ';
			$lines[] = '# A newer version of Lens is available:';
			$lines[] = '# ' . Url::LENS_INSTALLATION;
			$lines[] = '# ';
		}

		return implode("\n", $lines);
	}

	private function getCases(array $project)
	{
		$cases = [];

		$id = 0;

		foreach ($project['suites'] as $suiteFile => $suite) {
			$this->caseText->setSuite($suiteFile, $suite['namespace'], $suite['uses']);

			foreach ($suite['tests'] as $testLine => $test) {
				$this->caseText->setTest($test['code']);

				foreach ($test['cases'] as $caseLine => $case) {
					$this->caseText->setCase($caseLine, $case['cause'], $case['issues']);

					$cases[] = $this->getCaseText(++$id, $suiteFile, $caseLine, $case['issues']);
				}
			}
		}

		return $cases;
	}

	private function getCaseText($id, $testsFile, $caseLine, array $issues)
	{
		$titleText = "{$testsFile}:{$caseLine}";

		if ($this->isPassing($issues)) {
			return $this->getPassingCaseText($id, $titleText);
		}

		return $this->getFailingCaseText($id, $titleText);
	}

	// TODO: this is duplicated elsewhere
	private function isPassing(array $issues)
	{
		foreach ($issues as $issue) {
			if (is_array($issue)) {
				return false;
			}
		}

		return true;
	}

	private function getPassingCaseText($id, $titleText)
	{
		return "ok {$id} - {$titleText}";
	}

	private function getFailingCaseText($id, $titleText)
	{
		$caseText = $this->caseText->getText();
		$caseText = substr_replace($caseText, '*  ', 0, 3);
		$descriptionText = "---\n>\n{$caseText}\n...";
		$descriptionText = Paragraph::indent($descriptionText, '  ');

		return "not ok {$id} - {$titleText}\n{$descriptionText}";
	}

	private function getVersion()
	{
		return 'TAP version 13';
	}

	private function getPlan(array $cases)
	{
		$count = count($cases);

		return "1..{$count}";
	}
}
