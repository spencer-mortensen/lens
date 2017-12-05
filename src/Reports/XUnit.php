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

use Lens\Xml;

class XUnit implements Report
{
	public function getReport(array $suites)
	{
		foreach ($suites as $testsFile => $suite) {
			foreach ($suite['tests'] as $testLine => $test) {
				foreach ($test['cases'] as $caseLine => $case) {
					$cases[] = array($testsFile, $caseLine, $case['results']['pass']);
				}
			}
		}

		$project = 'MyProject';
		$passedTestsCount = 1;
		$failedTestsCount = 1;
		$innerXml = null;

		$output = array(
			self::getXmlTag(),
			self::getTestSuitesTag($project, $passedTestsCount, $failedTestsCount, $innerXml)
		);

		return implode("\n\n", $output) . "\n";
	}

	private static function getXmlTag()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>';
	}

	private static function getTestSuitesTag($name, $passedTestsCount, $failedTestsCount, $innerXml)
	{
		$attributes = array(
			'name' => $name,
			'tests' => $passedTestsCount,
			'failures' => $failedTestsCount
		);

		$innerXml = "\n{$innerXml}\n";

		return Xml::getElementXml('testsuites', $attributes, $innerXml);
	}
}
