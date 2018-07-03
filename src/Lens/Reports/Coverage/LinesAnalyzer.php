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

namespace _Lens\Lens\Reports\Coverage;

use _Lens\Lens\Citations;
use _Lens\Lens\SourcePaths;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;

class LinesAnalyzer
{
	/** @var SourcePaths */
	private $sourcePaths;

	/** @var Citations */
	private $citations;

	public function __construct(SourcePaths $sourcePaths, Citations $citations)
	{
		$this->sourcePaths = $sourcePaths;
		$this->citations = $citations;
	}

	public function getLines()
	{
		$classes = $this->citations->getClasses();
		$functions = $this->citations->getFunctions();
		$traits = $this->citations->getTraits();

		return [
			'classes' => $this->getClassLines($classes),
			'functions' => $this->getFunctionLines($functions),
			'traits' => $this->getTraitLines($traits)
		];
	}

	private function getClassLines(array $classes)
	{
		$output = [];

		foreach ($classes as $class) {
			$path = $this->sourcePaths->getLiveClassPath($class);
			$output[$class] = $this->getPathLines($path);
		}

		return $output;
	}

	private function getFunctionLines(array $functions)
	{
		$output = [];

		foreach ($functions as $function) {
			$path = $this->sourcePaths->getLiveFunctionPath($function);
			$output[$function] = $this->getPathLines($path);
		}

		return $output;
	}

	private function getTraitLines(array $traits)
	{
		$output = [];

		foreach ($traits as $trait) {
			$path = $this->sourcePaths->getLiveTraitPath($trait);
			$output[$trait] = $this->getPathLines($path);
		}

		return $output;
	}

	private function getPathLines(Path $path)
	{
		$file = new File($path);
		$contents = $file->read();
		return $this->getStringLines($contents);
	}

	// TODO: this is duplicated elsewhere
	private function getStringLines($text)
	{
		$expression = '\\r?\\n';

		$delimiter = "\x03";
		$pattern = "{$delimiter}{$expression}{$delimiter}XDs";

		return preg_split($pattern, $text);
	}
}
