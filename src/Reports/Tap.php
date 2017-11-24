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

class Tap implements Report
{
	public function getReport(array $suites)
	{
		$cases = self::getCases($suites);

		$output = array(
			self::getVersion(),
			self::getPlan($cases)
		);

		self::addTestLines($cases, $output);

		return implode("\n", $output) . "\n";
	}

	private static function getCases(array $suites)
	{
		$cases = array();

		foreach ($suites as $testsFile => $suite) {
			foreach ($suite['tests'] as $testLine => $test) {
				foreach ($test['cases'] as $caseLine => $case) {
					$cases[] = array($testsFile, $caseLine, $case['results']['pass']);
				}
			}
		}

		return $cases;
	}

	private static function getVersion()
	{
		return 'TAP version 13';
	}

	private static function getPlan(array $cases)
	{
		$count = count($cases);

		return "1..{$count}";
	}

	private static function addTestLines(array $cases, array &$output)
	{
		foreach ($cases as $i => $case) {
			list($testsFile, $caseLine, $isPassing) = $case;
			$id = $i + 1;

			$output[] = self::getCaseText($id, $testsFile, $caseLine, $isPassing);
		}
	}

	private static function getCaseText($id, $testsFile, $caseLine, $isPassing)
	{
		$passingText = self::getPassingText($isPassing);
		$descriptionText = self::getDescriptionText($testsFile, $caseLine);

		return "{$passingText} {$id} - {$descriptionText}";
	}

	private static function getPassingText($isPassing)
	{
		if ($isPassing) {
			return 'ok';
		}

		return 'not ok';
	}

	private static function getDescriptionText($testsFile, $caseLine)
	{
		return "{$testsFile}:{$caseLine}";
	}
}
