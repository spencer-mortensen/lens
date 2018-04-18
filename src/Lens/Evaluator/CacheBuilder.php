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
use Lens_0_0_56\Lens\Files\TextFile;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Php\Code;
use Lens_0_0_56\Lens\Watcher;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;
use ReflectionClass;
use ReflectionFunction;

/*

Assumptions:

 * The user autoloader uses "require_once" or "include_once" (rather than "require" or "include")
 * The "src" directory contains only source code (no controller scripts, or constant definitions, or global variables)
 * The "src" directory contains scripts that have no fatal syntax errors (i.e. all files must all be parseable)
 * The source files use the common namespace format (not the brace format) with parseable method calls

*/

class CacheBuilder
{
	/** @var string */
	private $projectDirectory;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $autoloadFile;

	/** @var Watcher */
	private $cacheDirectory;

	/** @var Paths  */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	public function __construct($projectDirectory, $srcDirectory, $autoloadFile, $cacheDirectory)
	{
		$this->projectDirectory = $projectDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->autoloadFile = $autoloadFile;
		$this->cacheDirectory = $cacheDirectory;
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
	}

	public function build()
	{
		$watcher = $this->getWatcher();
		$declarations = new Declarations();

		$changes = $watcher->getDirectoryStatus($this->srcDirectory);

		$declarations->start();
		$this->enableUserAutoloader($this->autoloadFile);

		foreach ($changes as $path => $exists) {
			if ($this->isPhpFile($path)) {
				require_once $path;
			}
		}

		// For each file:
		//   For each use statement:
		//     Autoload the class (using "class_exists($class, true)")
		//   Load all sibling files (if not already loaded)

		while ($declarations->get($path, $classes, $functions)) {
			if (isset($changes[$path]) || $watcher->isModifiedFile($path)) {
				$this->removeCachedFile($path);
				$this->addCachedFile($path, $classes, $functions);
			}
		}
	}

	private function getWatcher()
	{
		$filePath = $this->paths->join($this->cacheDirectory, 'modified.json');
		$cacheFile = new JsonFile($this->filesystem, $filePath);
		return new Watcher($cacheFile, $this->projectDirectory);
	}

	private function enableUserAutoloader($autoloadFile)
	{
		// TODO: exception handling
		require $autoloadFile;
	}

	// TODO: this is repeated elsewhere
	private function isPhpFile($path)
	{
		return substr($path, -4) === '.php';
	}

	private function removeCachedFile($path)
	{
		echo "remove from cache: $path\n";
	}

	private function addCachedFile($path, array $classes, array $functions)
	{
		foreach ($classes as $class) {
			$this->addLiveClass($class);
			$this->addMockClass($class);
		}

		foreach ($functions as $function) {
			$this->addLiveFunction($function);
			$this->addMockFunction($function);
		}
	}

	private function addLiveClass($class)
	{
		$path = $this->getLiveClassPath($class);
		$code = $this->getLiveClassCode($class);

		$file = new TextFile($this->filesystem, $path);
		$file->write($code);
	}

	private function addMockClass($class)
	{
		$path = $this->getMockClassPath($class);
		$code = $this->getMockClassCode($class);

		$file = new TextFile($this->filesystem, $path);
		$file->write($code);
	}

	private function addLiveFunction($function)
	{
		$path = $this->getLiveFunctionPath($function);
		$code = $this->getLiveFunctionCode($function);

		$file = new TextFile($this->filesystem, $path);
		$file->write($code);
	}

	private function addMockFunction($function)
	{
		$path = $this->getMockFunctionPath($function);
		$code = $this->getMockFunctionCode($function);

		$file = new TextFile($this->filesystem, $path);
		$file->write($code);
	}

	private function getLiveClassPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'classes', 'live', $relativePath) . '.php';
	}

	private function getMockClassPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'classes', 'mock', $relativePath) . '.php';
	}

	private function getLiveFunctionPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'functions', 'live', $relativePath) . '.php';
	}

	private function getMockFunctionPath($class)
	{
		$relativePath = $this->getRelativePath($class);
		return $this->paths->join($this->cacheDirectory, 'functions', 'mock', $relativePath) . '.php';
	}

	private function getRelativePath($namespacePath)
	{
		$parts = explode('\\', $namespacePath);
		return $this->paths->join($parts);
	}

	private function getLiveClassCode($class)
	{
		$builder = new LiveBuilder($this->cacheDirectory);
		return $builder->getClassPhp($class);
	}

	private function getLiveFunctionCode($function)
	{
		$builder = new LiveBuilder($this->cacheDirectory);
		return $builder->getFunctionPhp($function);
	}

	private function getMockClassCode($class)
	{
		// TODO: absorb this into the "MockBuilder"
		$reflection = new ReflectionClass($class);
		$namespace = $reflection->getNamespaceName();

		$uses = array(
			'Agent' => __NAMESPACE__ . '\\Agent'
		);

		$contextPhp = Code::getContextPhp($namespace, $uses);

		$builder = new MockBuilder();
		$classPhp = $builder->getMockClassPhp($class);

		return Code::getPhp($contextPhp, $classPhp);
	}

	private function getMockFunctionCode($function)
	{
		// TODO: absorb this into the "MockBuilder"
		$reflection = new ReflectionFunction($function);
		$namespace = $reflection->getNamespaceName();

		$uses = array(
			'Agent' => __NAMESPACE__ . '\\Agent'
		);

		$contextPhp = Code::getContextPhp($namespace, $uses);

		$builder = new MockBuilder();
		$functionPhp = $builder->getMockFunctionPhp($function);

		return Code::getPhp($contextPhp, $functionPhp);
	}
}
