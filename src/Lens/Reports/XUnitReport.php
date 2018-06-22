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

namespace Lens_0_0_57\Lens\Reports;

use Lens_0_0_57\Lens\Url;

class XUnitReport
{
	/** @var CaseText */
	private $caseText;

	public function __construct(CaseText $caseText)
	{
		$this->caseText = $caseText;
	}

	public function getReport(array $project, $isUpdateAvailable)
	{
		$output = [
			$this->getXmlTag(),
			$this->getProjectXml($project)
		];

		if ($isUpdateAvailable) {
			$output[] = $this->getUpgradeXml();
		}

		return implode("\n\n", $output);
	}

	private function getXmlTag()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>';
	}

	private function getProjectXml(array $project)
	{
		$passed = 0;
		$failed = 0;

		$suiteResults = [];

		foreach ($project['suites'] as $suiteFile => $suite) {
			$suiteResults[] = $this->getSuiteXml($suiteFile, $suite, $suitePassed, $suiteFailed);

			$passed += $suitePassed;
			$failed += $suiteFailed;
		}

		$innerXml = implode("\n", $suiteResults);

		$attributes = [
			'name' => $project['name'],
			'tests' => $passed + $failed,
			'failures' => $failed
		];

		return Xml::getElementXml('testsuites', $attributes, $innerXml);
	}

	private function getSuiteXml($suiteFile, array $suite, &$passed, &$failed)
	{
		$this->caseText->setSuite($suiteFile, $suite['namespace'], $suite['uses']);

		$passed = 0;
		$failed = 0;

		$testResults = [];

		foreach ($suite['tests'] as $testLine => $test) {
			$testResults[] = $this->getTestXml($test, $testPassed, $testFailed);

			$passed += $testPassed;
			$failed += $testFailed;
		}

		$innerXml = implode("\n", $testResults);

		$attributes = [
			'name' => $suiteFile,
			'tests' => $passed + $failed,
			'failures' => $failed
		];

		return Xml::getElementXml('testsuite', $attributes, $innerXml);
	}

	private function getTestXml(array $test, &$passed, &$failed)
	{
		$this->caseText->setTest($test['code']);

		$passed = 0;
		$failed = 0;

		$cases = [];

		foreach ($test['cases'] as $caseLine => $case) {
			$cases[] = $this->getCaseXml($caseLine, $case, $casePassed, $caseFailed);

			$passed += $casePassed;
			$failed += $caseFailed;
		}

		return implode("\n", $cases);
	}

	private function getCaseXml($caseLine, array $case, &$passed, &$failed)
	{
		$this->caseText->setCase($caseLine, $case['cause'], $case['issues']);

		if ($this->isPassing($case['issues'])) {
			$passed = 1;
			$failed = 0;
			$innerXml = null;
		} else {
			$passed = 0;
			$failed = 1;
			$innerXml = $this->getTestFailureXml();
		}

		$attributes = [
			'name' => "Line {$caseLine}"
		];

		return Xml::getElementXml('testcase', $attributes, $innerXml);
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

	private function getTestFailureXml()
	{
		$caseText = $this->caseText->getText();
		$innerXml = Xml::getTextXml($caseText);

		return Xml::getElementXml('failure', [], $innerXml);
	}

	private function getUpgradeXml()
	{
		return Xml::getCommentXml('A newer version of Lens is available: ' . Url::LENS_INSTALLATION);
	}
}
