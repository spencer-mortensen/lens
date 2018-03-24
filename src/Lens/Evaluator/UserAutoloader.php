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

use ReflectionFunction;

class UserAutoloader
{
	/** @var array */
	private $autoloaders;

	/** @var array */
	private $functions;

	/** @var array */
	private $classes;

	public function __construct($autoloaderPath)
	{
		$countAutoloaders = $this->countAutoloaders();
		$countClasses = $this->countDeclaredClasses();
		$countFunctions = $this->countDefinedFunctions();

		// TODO: add exception handling
		require $autoloaderPath;

		$this->autoloaders = $this->getUserAutoloaders($countAutoloaders);
		$this->classes = $this->getDeclaredClasses($countClasses);
		$this->functions = $this->getDefinedFunctions($countFunctions);

		spl_autoload_register(array($this, 'autoload'));
	}

	private function countAutoloaders()
	{
		$autoloaders = spl_autoload_functions();
		return count($autoloaders);
	}

	private function getUserAutoloaders($count)
	{
		$autoloaders = spl_autoload_functions();
		$autoloaders = array_slice($autoloaders, $count);

		foreach ($autoloaders as $autoloader) {
			spl_autoload_unregister($autoloader);
		}

		return $autoloaders;
	}

	private function countDeclaredClasses()
	{
		$classes = get_declared_classes();
		return count($classes);
	}

	private function getDeclaredClasses($count)
	{
		$classes = get_declared_classes();
		return array_slice($classes, $count);
	}

	private function countDefinedFunctions()
	{
		$functions = get_defined_functions()['user'];
		return count($functions);
	}

	private function getDefinedFunctions($count)
	{
		$functions = get_defined_functions()['user'];
		$functions = array_slice($functions, $count);
		return self::getFullFunctionNames($functions);
	}

	private static function getFullFunctionNames(array $names)
	{
		$functions = array();

		foreach ($names as $name) {
			$reflection = new ReflectionFunction($name);
			$functions[] = $reflection->getName();
		}

		return $functions;
	}

	public function declareClass($class)
	{
		if (class_exists($class, false)) {
			return true;
		}

		$countClasses = $this->countDeclaredClasses();
		$countFunctions = $this->countDefinedFunctions();

		$isDeclared = $this->autoload($class);

		$classes = $this->getDeclaredClasses($countClasses);
		$functions = $this->getDefinedFunctions($countFunctions);

		$this->classes = array_merge($this->classes, $classes);
		$this->functions = array_merge($this->functions, $functions);

		return $isDeclared;
	}

	private function autoload($class)
	{
		foreach ($this->autoloaders as $autoloader) {
			// TODO: add exception handling
			call_user_func($autoloader, $class);

			if (class_exists($class, false)) {
				return true;
			}
		}

		return false;
	}

	public function getClasses()
	{
		return $this->classes;
	}

	public function getFunctions()
	{
		return $this->functions;
	}
}
