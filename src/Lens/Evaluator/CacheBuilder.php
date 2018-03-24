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

use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Map;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;
use ReflectionClass;

class CacheBuilder
{
	/** @var Paths  */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $autoload;

	/** @var string */
	private $cache;

	public function __construct($autoload, $cache)
	{
		// TODO: dependency injection
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->autoload = $autoload;
		$this->cache = $cache;
	}

	public function run($class)
	{
		$autoloader = new UserAutoloader($this->autoload);
		$isDeclared = $autoloader->declareClass($class);

		// TODO: if the class has *not* been declared, and the global version is
		// a dangerous class, then create a local mock of that global class

		// TODO: otherwise, make a note of the fact that there is nothing to mock

		$this->writeClasses($autoloader);
		$this->writeFunctions($autoloader);

		return true;
	}

	private function writeClasses(UserAutoloader $autoloader)
	{
		$classes = $autoloader->getClasses();

		// TODO: this path is repeated elsewhere:
		$parentsPath = $this->paths->join($this->cache, 'classes', 'parents.json');
		$parentsMap = new Map($this->paths, $this->filesystem, $parentsPath);

		foreach ($classes as $class) {
			$cache = new CachedClass($this->cache, $class);
			$cache->set();

			$reflection = new ReflectionClass($class);
			$parentReflection = $reflection->getParentClass();

			if ($parentReflection !== false) {
				$parent = $parentReflection->getName();
				// TODO: do this all at once, rather than as a sequence of slow lock-write steps
				$parentsMap->set($class, $parent);
			}
		}
	}

	private function writeFunctions(UserAutoloader $autoloader)
	{
		$functions = $autoloader->getFunctions();

		foreach ($functions as $function) {
			$cache = new CachedFunction($this->cache, $function);
			$cache->set();
		}
	}
}
