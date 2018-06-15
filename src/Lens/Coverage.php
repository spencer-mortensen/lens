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

namespace Lens_0_0_56\Lens;

use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;

class Coverage
{
	/** @var JsonFile */
	private $file;

	/** @var array */
	private $classes;

	/** @var array */
	private $functions;

	/** @var array */
	private $traits;

	public function __construct(Path $cache)
	{
		$path = $cache->add('coverage.json');
		$file = new JsonFile($path);
		$coverage = $file->read();

		$this->file = $file;
		$this->coverage = $coverage;

		if (!is_array($coverage)) {
			$coverage = [];
		}

		$this->classes = self::getArray($coverage['classes']);
		$this->functions = self::getArray($coverage['functions']);
		$this->traits = self::getArray($coverage['traits']);
	}

	private static function getArray(&$value)
	{
		if (!is_array($value)) {
			$value = [];
		}

		return $value;
	}

	public function getCoverage()
	{
		return [
			'classes' => $this->classes,
			'functions' => $this->functions,
			'traits' => $this->traits
		];
	}

	public function setClass($class, array $coverage = null)
	{
		$this->classes[$class] = $coverage;
	}

	public function unsetClass($class)
	{
		unset($this->classes[$class]);
	}

	public function setFunction($function, array $coverage = null)
	{
		$this->functions[$function] = $coverage;
	}

	public function unsetFunction($function)
	{
		unset($this->functions[$function]);
	}

	public function setTrait($trait, array $coverage = null)
	{
		$this->traits[$trait] = $coverage;
	}

	public function unsetTrait($trait)
	{
		unset($this->traits[$trait]);
	}

	public function __destruct()
	{
		$coverage = $this->getCoverage();

		if ($coverage !== $this->coverage) {
			$this->file->write($coverage);
		}
	}
}
