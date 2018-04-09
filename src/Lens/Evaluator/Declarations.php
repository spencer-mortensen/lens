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

	/** @var string */
	private $lensPrefix;

	/** @var integer */
	private $lensPrefixLength;

	public function start()
	{
		$this->files = array();
		$this->countClasses = $this->countDeclaredClasses();
		$this->countFunctions = $this->countDefinedFunctions();
		$this->lensPrefix = $this->getLensNamespacePrefix();
		$this->lensPrefixLength = strlen($this->lensPrefix);
	}

	public function get(&$path, &$classes, &$functions)
	{
		if (count($this->files) === 0) {
			$this->getDeclaredClasses();
			$this->getDefinedFunctions();
		}

		if (count($this->files) === 0) {
			return false;
		}

		$path = key($this->files);
		$classes = $this->files[$path]['classes'];
		$functions = $this->files[$path]['functions'];
		unset($this->files[$path]);

		return true;
	}

	private function countDeclaredClasses()
	{
		$classes = get_declared_classes();
		return count($classes);
	}

	private function getDeclaredClasses()
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

			// TODO
			if (!isset($this->files[$file])) {
				$this->files[$file] = array(
					'classes' => array(),
					'functions' => array()
				);
			}

			$this->files[$file]['classes'][] = $class;
		}
	}

	private function countDefinedFunctions()
	{
		$functions = get_defined_functions()['user'];
		return count($functions);
	}

	private function getDefinedFunctions()
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

			// TODO
			if (!isset($this->files[$file])) {
				$this->files[$file] = array(
					'classes' => array(),
					'functions' => array()
				);
			}

			$this->files[$file]['functions'][] = $function;
		}
	}

	private function getLensNamespacePrefix()
	{
		$names = explode('\\', __NAMESPACE__);
		return array_shift($names) . '\\';
	}

	private function isLensName($name)
	{
		return strncmp($name, $this->lensPrefix, $this->lensPrefixLength) === 0;
	}
}
