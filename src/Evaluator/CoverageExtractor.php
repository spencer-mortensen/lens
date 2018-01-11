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

namespace Lens\Evaluator;

use SpencerMortensen\RegularExpressions\Re;

class CoverageExtractor
{
	/** @var boolean */
	private $isCoverageEnabled;

	/** @var string */
	private $srcDirectory;

	/** @var null|array */
	private $coverage;

	public function __construct($srcDirectory)
	{
		$this->isCoverageEnabled = function_exists('xdebug_start_code_coverage');
		$this->srcDirectory = $srcDirectory;
	}

	public function start()
	{
		if (!$this->isCoverageEnabled) {
			return;
		}

		xdebug_start_code_coverage();
	}

	public function stop()
	{
		if (!$this->isCoverageEnabled) {
			return;
		}

		$coverage = xdebug_get_code_coverage();
		xdebug_stop_code_coverage();

		$this->coverage = $this->getCleanCoverage($coverage);
	}

	public function getCoverage()
	{
		return $this->coverage;
	}

	private function getCleanCoverage(array $coverage)
	{
		$output = array();

		foreach ($coverage as $path => $lines) {
			if (!$this->isRelevant($path)) {
				continue;
			}

			$output[$path] = self::getCleanLines($lines);
		}

		return $output;
	}

	private function isRelevant(&$path)
	{
		return $this->isSourceFile($path) && !self::isEvaluated($path);
	}

	private function isSourceFile(&$path)
	{
		$prefix = $this->srcDirectory . '/';
		$prefixLength = strlen($prefix);

		if (strncmp($path, $prefix, $prefixLength) !== 0) {
			return false;
		}

		$path = substr($path, $prefixLength);
		return true;
	}

	private static function isEvaluated($path)
	{
		return Re::match('\\([0-9]+\\) : eval\\(\\)\'d code$', $path);
	}

	private static function getCleanLines(array $input)
	{
		$output = array();

		foreach ($input as $lineNumber => $status) {
			$output[] = $lineNumber - 1;
		}

		return $output;
	}
}
