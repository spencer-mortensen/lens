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
	public function getReport(array $project)
	{
		$output = array(
			self::getXmlTag(),
			self::getProjectXml($project)
		);

		return implode("\n\n", $output) . "\n";
	}

	private static function getXmlTag()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>';
	}

	private static function getProjectXml($project)
	{
		$element = 'testsuites';

		$attributes = array(
			'name' => $project['name'],
			'tests' => $project['summary']['passed'],
			'failures' => $project['summary']['failed']
		);

		$children = array();

		foreach ($project['suites'] as $file => $suite) {
			$children[] = self::getSuiteXml($file, $suite);
		}

		$innerXml = implode("\n", $children);

		return Xml::getElementXml($element, $attributes, $innerXml);
	}

	private static function getSuiteXml($name, array $suite)
	{
		$element = 'testsuite';

		$attributes = array(
			'name' => $name,
			'tests' => $suite['summary']['passed'],
			'failures' => $suite['summary']['failed']
		);

		$children = array();

		foreach ($suite['tests'] as $line => $test) {
			$children[] = self::getTestXml($line, $test);
		}

		$innerXml = implode("\n", $children);

		return Xml::getElementXml($element, $attributes, $innerXml);
	}

	private static function getTestXml($testLine, array $test)
	{
		$children = array();

		foreach ($test['cases'] as $caseLine => $case) {
			$children[] = self::getCaseXml("Line {$caseLine}", $case);
		}

		// TODO: return null if there are no test cases
		return implode("\n", $children);
	}

	private static function getCaseXml($name, array $case)
	{
		if ($case['summary']['pass']) {
			$innerXml = null;
		} else {
			$innerXml = self::getTestFailureXml($case);
		}

		return self::getTestCaseXml($name, $innerXml);
	}

	private static function getTestFailureXml(array $case)
	{
		// TODO: show the actual test issues
		return Xml::getElementXml('failure', array(), 'The test failed!');
	}

	private static function getTestCaseXml($name, $innerXml)
	{
		$attributes = array(
			'name' => $name
		);

		return Xml::getElementXml('testcase', $attributes, $innerXml);
	}
}
