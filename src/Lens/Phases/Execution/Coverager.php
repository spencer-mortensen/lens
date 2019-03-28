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

namespace _Lens\Lens\Phases\Execution;

use _Lens\Lens\Phases\Finder;
use _Lens\Lens\JsonFile;
use _Lens\Lens\Paragraph;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class Coverager
{
	/** @var Filesystem */
	private $filesystem;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	public function getCoverage(Finder $finder)
	{
		if (!Xdebug::isEnabled()) {
			return;
		}

		// TODO: abstract this into a class, and absorb the error-handling code from elsewhere:
		$coveragePath = $finder->getCoveragePath();
		$coverageFile = new JsonFile($coveragePath);
		$data = $coverageFile->read();

		$missing = $this->getMissingCoverage($finder, $data);
		$xdebugCoverage = $this->getXdebugCoverage($finder, $data, $missing);
		$this->setMissingCoverage($xdebugCoverage, $missing);

		$coverageFile->write($data);
		$this->deleteXdebugFiles($finder);
	}

	private function getMissingCoverage(Finder $finder, array &$data)
	{
		$livePath = $finder->getLivePath();
		$xdebugPath = $finder->getXdebugPath();

		$missing = [
			'classes' => [],
			'functions' => []
		];

		foreach ($data['classes'] as $class => &$coverage) {
			if ($coverage === null) {
				$pathString = (string)$finder->getClassPath($livePath, $class);
				$missing['classes'][$pathString] = &$coverage;
			}
		}

		foreach ($data['functions'] as $function => &$coverage) {
			if ($coverage === null) {
				$pathString = (string)$finder->getFunctionPath($xdebugPath, $function);
				$missing['functions'][$pathString] = &$coverage;
			}
		}

		return $missing;
	}

	private function getXdebugCoverage(Finder $finder, array $data, array $missing)
	{
		$autoloader = new Autoloader($this->filesystem, $finder);
		$autoloader->enable();

		$xdebug = new Xdebug(false);
		$xdebug->start();

		foreach ($data['classes'] as $class => $coverage) {
			if ($coverage === null) {
				class_exists($class, true);
			}
		}

		foreach ($missing['functions'] as $pathString => $coverage) {
			include $pathString;
		}

		$xdebug->stop();
		return $xdebug->getCoverage();
	}

	private function setMissingCoverage(array $xdebugCoverage, array $missing)
	{
		foreach ($missing['classes'] as $pathString => &$coverage) {
			$lines = $this->getLines($pathString);
			$coverage = $this->getCleanClassCoverage($xdebugCoverage[$pathString], $lines);
		}

		foreach ($missing['functions'] as $pathString => &$coverage) {
			$coverage = $this->getCleanFunctionCoverage($xdebugCoverage[$pathString]);
		}
	}

	private function getLines($pathString)
	{
		$path = Path::fromString($pathString);
		$file = new File($path);
		$php = $file->read();

		if ($php === null) {
			return null;
		}

		$php = Paragraph::standardizeNewlines($php);
		return explode("\n", $php);
	}

	private function getCleanClassCoverage(array $coverage, array $lines)
	{
		$output = [];

		foreach ($coverage as $lineNumber => $status) {
			--$lineNumber;

			$lineText = &$lines[$lineNumber];

			if ($this->isStatement($lineText)) {
				$output[] = $lineNumber;
			}
		}

		return $output;
	}

	// TODO: this is fragile:
	private function isStatement($code)
	{
		$code = trim($code);

		if (strlen(trim($code, '{}')) === 0) {
			return false;
		}

		// TODO: handle "public function add(...$arguments)"
		// TODO: handle "use Flier;" statements (these are always executed)
		// TODO: handle "abstract" and "final" classes (e.g. "abstract class Path")
		if (substr($code, 0, 6) === 'class ') {
			return false;
		}

		if (substr($code, 0, 6) === 'trait ') {
			return false;
		}

		return true;
	}

	private function getCleanFunctionCoverage(array $coverage)
	{
		$iBegin = key($coverage);
		end($coverage);
		$iEnd = key($coverage);

		unset(
			$coverage[$iBegin],
			$coverage[$iBegin + 1],
			$coverage[$iEnd - 2],
			$coverage[$iEnd - 1],
			$coverage[$iEnd]
		);

		$output = [];

		foreach ($coverage as $lineNumber => $status) {
			$output[] = $lineNumber - 2;
		}

		return $output;
	}

	private function deleteXdebugFiles(Finder $finder)
	{
		$path = $finder->getXdebugPath();
		$directory = new Directory($path);
		$directory->delete();
	}
}
