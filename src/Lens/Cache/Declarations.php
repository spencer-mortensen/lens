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

namespace Lens_0_0_57\Lens\Cache;

use ReflectionClass;
use ReflectionFunction;

class Declarations
{
	/** @var array */
	private $files;

	/** @var integer */
	private $countClasses;

	/** @var integer */
	private $countFunctions;

	/** @var integer */
	private $countInterfaces;

	/** @var integer */
	private $countTraits;

	/** @var string */
	private $lensPrefix;

	/** @var integer */
	private $lensPrefixLength;

	public function start()
	{
		$this->files = [];
		$this->countClasses = $this->countClasses();
		$this->countFunctions = $this->countFunctions();
		$this->countInterfaces = $this->countInterfaces();
		$this->countTraits = $this->countTraits();
		$this->lensPrefix = $this->getLensNamespacePrefix();
		$this->lensPrefixLength = strlen($this->lensPrefix);
	}

	public function get(&$path, &$classes, &$functions, &$interfaces, &$traits)
	{
		if (count($this->files) === 0) {
			$this->getClasses();
			$this->getFunctions();
			$this->getInterfaces();
			$this->getTraits();
		}

		if (count($this->files) === 0) {
			$classes = [];
			$functions = [];
			$interfaces = [];
			$traits = [];
			return false;
		}

		$path = key($this->files);
		$file = &$this->files[$path];

		$classes = self::getArray($file['classes']);
		$functions = self::getArray($file['functions']);
		$interfaces = self::getArray($file['interfaces']);
		$traits = self::getArray($file['traits']);

		unset($this->files[$path]);
		return true;
	}

	private function countClasses()
	{
		$classes = get_declared_classes();
		return count($classes);
	}

	private function countFunctions()
	{
		$functions = get_defined_functions()['user'];
		return count($functions);
	}

	private function countInterfaces()
	{
		$interfaces = get_declared_interfaces();
		return count($interfaces);
	}

	private function countTraits()
	{
		$traits = get_declared_traits();
		return count($traits);
	}

	private function getClasses()
	{
		$count = $this->countClasses;
		$classes = get_declared_classes();
		$this->countClasses = count($classes);
		$classes = array_slice($classes, $count);

		foreach ($classes as $class) {
			if ($this->isLensName($class)) {
				continue;
			}

			$reflection = new ReflectionClass($class);
			$file = $reflection->getFileName();

			$this->files[$file]['classes'][] = $class;
		}
	}

	private function getFunctions()
	{
		$count = $this->countFunctions;
		$functions = get_defined_functions()['user'];
		$this->countFunctions = count($functions);
		$functions = array_slice($functions, $count);

		foreach ($functions as $name) {
			$reflection = new ReflectionFunction($name);
			$function = $reflection->getName();

			if ($this->isLensName($function)) {
				continue;
			}

			$file = $reflection->getFileName();

			$this->files[$file]['functions'][] = $function;
		}
	}

	private function getInterfaces()
	{
		$count = $this->countInterfaces;
		$interfaces = get_declared_interfaces();
		$this->countInterfaces = count($interfaces);
		$interfaces = array_slice($interfaces, $count);

		foreach ($interfaces as $interface) {
			if ($this->isLensName($interface)) {
				continue;
			}

			$reflection = new ReflectionClass($interface);
			$file = $reflection->getFileName();

			$this->files[$file]['interfaces'][] = $interface;
		}
	}

	private function getTraits()
	{
		$count = $this->countTraits;
		$traits = get_declared_traits();
		$this->countTraits = count($traits);
		$traits = array_slice($traits, $count);

		foreach ($traits as $trait) {
			if ($this->isLensName($trait)) {
				continue;
			}

			$reflection = new ReflectionClass($trait);
			$file = $reflection->getFileName();

			$this->files[$file]['traits'][] = $trait;
		}
	}

	private function getLensNamespacePrefix()
	{
		// TODO: this is fragile, because it could break if the directory structure changes:
		$names = explode('\\', __NAMESPACE__);
		return array_shift($names) . '\\';
	}

	private function isLensName($name)
	{
		return strncmp($name, $this->lensPrefix, $this->lensPrefixLength) === 0;
	}

	private static function getArray(&$value)
	{
		if (is_array($value)) {
			return $value;
		}

		return [];
	}
}
