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

namespace _Lens\Lens;

use _Lens\SpencerMortensen\Filesystem\Path;

class Citations
{
	/** @var JsonFile */
	private $file;

	/** @var mixed */
	private $citations;

	/** @var array */
	private $classes;

	/** @var array */
	private $functions;

	/** @var array */
	private $interfaces;

	/** @var array */
	private $traits;

	public function __construct(Path $cache)
	{
		$path = $cache->add('citations.json');
		$file = new JsonFile($path);
		$citations = $file->read();

		$this->file = $file;
		$this->citations = $citations;

		if (!is_array($citations)) {
			$citations = [];
		}

		$this->classes = self::getArray($citations['classes']);
		$this->functions = self::getArray($citations['functions']);
		$this->interfaces = self::getArray($citations['interfaces']);
		$this->traits = self::getArray($citations['traits']);
	}

	private static function getArray(&$value)
	{
		if (!is_array($value)) {
			$value = [];
		}

		return $value;
	}

	public function addClass($class, $file)
	{
		$this->classes[$class] = $file;
	}

	public function getClasses()
	{
		return array_keys($this->classes);
	}

	public function addFunction($function, $file)
	{
		$this->functions[$function] = $file;
	}

	public function getFunctions()
	{
		return array_keys($this->functions);
	}

	public function addInterface($interface, $file)
	{
		$this->interfaces[$interface] = $file;
	}

	public function getInterfaces()
	{
		return array_keys($this->interfaces);
	}

	public function addTrait($trait, $file)
	{
		$this->traits[$trait] = $file;
	}

	public function getTraits()
	{
		return array_keys($this->traits);
	}

	public function remove($file, array &$classes = null, array &$functions = null, array &$interfaces = null, array &$traits = null)
	{
		$classes = self::extractMatches($this->classes, $file);
		$functions = self::extractMatches($this->functions, $file);
		$interfaces = self::extractMatches($this->interfaces, $file);
		$traits = self::extractMatches($this->traits, $file);
	}

	private static function extractMatches(array &$input, $target)
	{
		$output = [];

		foreach ($input as $key => $value) {
			if ($value === $target) {
				unset($input[$key]);
				$output[] = $key;
			}
		}

		return $output;
	}

	public function __destruct()
	{
		$citations = [
			'classes' => $this->classes,
			'functions' => $this->functions,
			'interfaces' => $this->interfaces,
			'traits' => $this->traits
		];

		if ($citations !== $this->citations) {
			$this->file->write($citations);
		}
	}
}
