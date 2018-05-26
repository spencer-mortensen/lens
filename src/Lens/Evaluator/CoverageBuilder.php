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

namespace Lens_0_0_56\Lens\Evaluator;

use Lens_0_0_56\Lens\Files\JsonFile;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Jobs\CoverageJob;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class CoverageBuilder
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensCoreDirectory;

	/** @var string|null */
	private $srcDirectory;

	/** @var string */
	private $cacheDirectory;

	/** @var JsonFile */
	private $cacheFile;

	/** @var array */
	private $coverage;

	/** @var Processor */
	private $processor;

	/** @var Paths  */
	private $paths;

	public function __construct($executable, $lensCoreDirectory, $srcDirectory, $cacheDirectory, Processor $processor)
	{
		$paths = Paths::getPlatformPaths();
		$filesystem = new Filesystem();
		$cacheFilePath = $paths->join($cacheDirectory, 'coverage.json');
		$cacheFile = new JsonFile($filesystem, $cacheFilePath);

		$this->executable = $executable;
		$this->lensCoreDirectory = $lensCoreDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->cacheDirectory = $cacheDirectory;
		$this->cacheFile = $cacheFile;
		$this->processor = $processor;
		$this->paths = $paths;
	}

	public function start()
	{
		if (!isset($this->srcDirectory) || !Xdebug::isEnabled()) {
			return;
		}

		$this->coverage = $this->readCoverage();

		$this->getClasses();
		$this->getFunctions();
		$this->getTraits();
	}

	// TODO: is this still necessary?
	private function readCoverage()
	{
		// TODO: error handling:
		$this->cacheFile->read($coverage);

		if (!is_array($coverage)) {
			$coverage = array();
		}

		$classes = &$coverage['classes'];

		if (!is_array($classes)) {
			$classes = array();
		}

		$functions = &$coverage['functions'];

		if (!is_array($functions)) {
			$functions = array();
		}

		$traits = &$coverage['traits'];

		if (!is_array($traits)) {
			$traits = array();
		}

		return $coverage;
	}

	private function getClasses()
	{
		$classes = &$this->coverage['classes'];

		foreach ($classes as $class => &$lineNumbers) {
			if (isset($lineNumbers)) {
				continue;
			}

			$path = $this->getLiveClassPath($class);
			$job = new CoverageJob($this->executable, $this->lensCoreDirectory, $this->cacheDirectory, $path, $lineNumbers);
			$this->processor->run($job);
		}
	}

	private function getFunctions()
	{
		$functions = &$this->coverage['functions'];

		foreach ($functions as $function => &$lineNumbers) {
			if (isset($lineNumbers)) {
				continue;
			}

			$path = $this->getLiveFunctionPath($function);
			$job = new CoverageJob($this->executable, $this->lensCoreDirectory, $this->cacheDirectory, $path, $lineNumbers);
			$this->processor->run($job);
		}
	}

	private function getTraits()
	{
		$traits = &$this->coverage['traits'];

		foreach ($traits as $trait => &$lineNumbers) {
			if (isset($lineNumbers)) {
				continue;
			}

			$path = $this->getLiveTraitPath($trait);
			$job = new CoverageJob($this->executable, $this->lensCoreDirectory, $this->cacheDirectory, $path, $lineNumbers);
			$this->processor->run($job);
		}
	}

	// TODO: this is duplicated elsewhere:
	private function getLiveClassPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'classes', 'live', $relativePath);
	}

	// TODO: this is duplicated elsewhere:
	private function getLiveFunctionPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'functions', 'live', $relativePath);
	}

	// TODO: this is duplicated elsewhere:
	private function getLiveTraitPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'traits', 'live', $relativePath);
	}

	// TODO: this is duplicated elsewhere:
	private function getRelativePath($namespacePath)
	{
		$parts = explode('\\', $namespacePath);
		return $this->paths->join($parts) . '.php';
	}

	public function stop()
	{
		if (!is_array($this->coverage)) {
			return;
		}

		// TODO: write this file only if the contents have changed:
		$this->cacheFile->write($this->coverage);
	}

	public function getCoverage()
	{
		return $this->coverage;
	}
}
