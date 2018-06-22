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

namespace Lens_0_0_57\Lens\Reports\Coverage;

use Lens_0_0_57\Lens\Citations;
use Lens_0_0_57\Lens\SourcePaths;
use Lens_0_0_57\SpencerMortensen\Filesystem\Filesystem;
use Lens_0_0_57\SpencerMortensen\Filesystem\Paths\Path;

class CoverageDataBuilder
{
	/** @var Path */
	private $core;

	/** @var Path */
	private $cache;

	/** @var Filesystem */
	private $filesystem;

	public function __construct(Path $core, Path $cache, Filesystem $filesystem)
	{
		$this->core = $core;
		$this->cache = $cache;
		$this->filesystem = $filesystem;
	}

	public function build(array $executableStatements, array $results)
	{
		$sourcePaths = new SourcePaths($this->filesystem, $this->core, $this->cache);
		$citations = new Citations($this->cache);
		$linesAnalyzer = new LinesAnalyzer($sourcePaths, $citations);
		$executedStatementsAnalyzer = new ExecutedStatementsAnalyzer();

		$output = [];

		$code = $linesAnalyzer->getLines();
		$coverage = $executedStatementsAnalyzer->getExecutedStatements($executableStatements, $results);

		$this->addCoverage('class', $code['classes'], $coverage['classes'], $output);
		$this->addCoverage('function', $code['functions'], $coverage['functions'], $output);
		$this->addCoverage('trait', $code['traits'], $coverage['traits'], $output);

		return $output;
	}

	private function addCoverage($type, array $lines, array $map, array &$output)
	{
		foreach ($lines as $namespacePath => $code) {
			$coverage = &$map[$namespacePath];
			$this->addTypeCoverage($type, $namespacePath, $code, (array)$coverage, $output);
		}
	}

	private function addTypeCoverage($type, $namespacePath, array $code, array $coverage, array &$output)
	{
		$names = explode('\\', $namespacePath);
		$lastName = array_pop($names);

		foreach ($names as $name) {
			$output = &$output[$name]['.name'];
		}

		$output[$lastName][".{$type}"] = [
			'code' => $code,
			'coverage' => $coverage
		];
	}
}
