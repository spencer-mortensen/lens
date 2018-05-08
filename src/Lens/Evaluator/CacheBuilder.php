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
use Lens_0_0_56\Lens\Php\FileParser;
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

	/** @var Watcher */
	private $watcher;

	/** @var FileParser */
	private $parser;

	/** @var Sanitizer */
	private $sanitizer;

	/** @var array */
	private $scannedDirectories;

	/** @var array */
	private $modifiedFiles;

	public function __construct($projectDirectory, $srcDirectory, $autoloadFile, $cacheDirectory, array $mockFunctions)
	{
		$this->projectDirectory = $projectDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->autoloadFile = $autoloadFile;
		$this->cacheDirectory = $cacheDirectory;
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->watcher = $this->getWatcher();
		$this->parser = new FileParser();
		$this->sanitizer = new Sanitizer('function_exists', $mockFunctions);
	}

	private function getWatcher()
	{
		$filePath = $this->paths->join($this->cacheDirectory, 'modified.json');
		$cacheFile = new JsonFile($this->filesystem, $filePath);
		return new Watcher($cacheFile, $this->projectDirectory);
	}

	public function build()
	{
		$this->scannedDirectories = array();
		$this->modifiedFiles = array();

		$directories = array();

		$this->getChildDirectories($this->srcDirectory, $directories);

		$declarations = new Declarations();
		$declarations->start();

		if ($this->watcher->isModifiedFile($this->autoloadFile)) {
			$this->modifiedFiles[$this->autoloadFile] = $this->autoloadFile;
		}

		$this->requireOnce($this->autoloadFile);

		foreach ($directories as $directory) {
			$this->scanDirectory($directory);
		}

		while ($declarations->get($file, $classes, $functions, $interfaces, $traits)) {
			if ($file !== $this->autoloadFile) {
				$parentDirectory = dirname($file);

				$this->scanDirectory($parentDirectory);
			}

			$this->addFile($file, $classes, $functions, $interfaces, $traits);
		}
	}

	private function getChildDirectories($directoryPath, array &$directories)
	{
		$directories[$directoryPath] = $directoryPath;

		$contents = $this->filesystem->scan($directoryPath);

		foreach ($contents as $childName) {
			$childPath = $this->paths->join($directoryPath, $childName);

			if (is_dir($childPath)) {
				$this->getChildDirectories($childPath, $directories);
			}
		}
	}

	private function scanDirectory($directoryPath)
	{
		$scannedDirectory = &$this->scannedDirectories[$directoryPath];

		if ($scannedDirectory !== null) {
			return;
		}

		$changes = $this->watcher->getDirectoryChanges($directoryPath);

		foreach ($changes as $filePath => $exists) {
			if (!$this->isPhpFile($filePath)) {
				continue;
			}

			// TODO: if the file is modified or deleted:
			$this->removeFile($filePath);

			// TODO: if the file is new or modified:
			if ($exists) {
				$this->requireOnce($filePath);

				$this->modifiedFiles[$filePath] = $filePath;
			}
		}

		$scannedDirectory = $directoryPath;
	}

	// TODO: this is repeated elsewhere
	private function isPhpFile($path)
	{
		return substr($path, -4) === '.php';
	}

	private function removeFile($file)
	{
		echo "remove: $file\n";
	}

	private function requireOnce($filePath)
	{
		// TODO: exception handling
		require_once $filePath;
	}

	private function addFile($file, array $classes, array $functions, array $interfaces, array $traits)
	{
		if (!$this->isModifiedFile($file)) {
			return;
		}

		$filePhp = $this->filesystem->read($file);

		list($namespace, $uses) = $this->parser->parse($filePhp);

		// TODO: What if  the $path is not a fully-qualified class name? (Maybe it's a partial namespace path?)
		// TODO: Support the function and constant use-statements: http://php.net/manual/en/language.namespaces.importing.php
		foreach ($uses as $alias => $path) {
			class_exists($path, true);
		}

		foreach ($classes as $class) {
			$this->addLiveClass($class, $namespace, $uses, $filePhp);
			$this->addMockClass($class);
		}

		foreach ($functions as $function) {
			$this->addLiveFunction($function, $namespace, $uses, $filePhp);
			$this->addMockFunction($function);
		}

		foreach ($interfaces as $interface) {
			// TODO: support interfaces
		}

		foreach ($traits as $trait) {
			// TODO: support traits
		}
	}

	private function isModifiedFile($filePath)
	{
		return isset($this->modifiedFiles[$filePath]);
	}

	// TODO: use the regular expressions class:
	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return $delimiter . $expression . $delimiter . $flags . 'XDs';
	}

	private function addLiveClass($class, $namespace, array $uses, $filePhp)
	{
		$path = $this->getLiveClassPath($class);

		$reflection = new ReflectionClass($class);
		$classPhp = $this->getDefinitionPhp($reflection, $filePhp);
		$classPhp = $this->sanitizer->sanitize('class', $namespace, $uses, $classPhp);

		$this->write($namespace, $uses, $classPhp, $path);
	}

	/**
	 * @param ReflectionClass|ReflectionFunction $reflection
	 * @param string $filePhp
	 * @return string
	 */
	private function getDefinitionPhp($reflection, $filePhp)
	{
		$pattern = self::getPattern('\\r?\\n');
		$lines = preg_split($pattern, $filePhp);

		$begin = $reflection->getStartLine() - 1;
		$length = $reflection->getEndLine() - $begin;

		$lines = array_slice($lines, $begin, $length);
		return implode("\n", $lines);
	}

	private function write($namespace, array $uses, $definitionPhp, $path)
	{
		$tagPhp = Code::getTagPhp();
		$contextPhp = Code::getContextPhp($namespace, $uses);
		$filePhp = Code::combine($tagPhp, $contextPhp, $definitionPhp) . "\n";

		$file = new TextFile($this->filesystem, $path);
		$file->write($filePhp);
	}

	private function addMockClass($class)
	{
		$path = $this->getMockClassPath($class);
		$code = $this->getMockClassCode($class);

		$file = new TextFile($this->filesystem, $path);
		$file->write($code);
	}

	private function addLiveFunction($function, $namespace, array $uses, $filePhp)
	{
		$path = $this->getLiveFunctionPath($function);

		$reflection = new ReflectionFunction($function);
		$functionPhp = $this->getDefinitionPhp($reflection, $filePhp);
		$functionPhp = $this->sanitizer->sanitize('function', $namespace, $uses, $functionPhp);

		$this->write($namespace, $uses, $functionPhp, $path);
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

	private function getMockClassCode($class)
	{
		// TODO: absorb this into the "MockBuilder"
		$reflection = new ReflectionClass($class);
		$namespace = $reflection->getNamespaceName();

		$uses = array(
			'Agent' => __NAMESPACE__ . '\\Agent'
		);

		$tagPhp = Code::getTagPhp();
		$contextPhp = Code::getContextPhp($namespace, $uses);

		$builder = new MockBuilder();
		$classPhp = $builder->getMockClassPhp($class);

		return Code::combine($tagPhp, $contextPhp, $classPhp) . "\n";
	}

	private function getMockFunctionCode($function)
	{
		// TODO: absorb this into the "MockBuilder"
		$reflection = new ReflectionFunction($function);
		$namespace = $reflection->getNamespaceName();

		$uses = array(
			'Agent' => __NAMESPACE__ . '\\Agent'
		);

		$tagPhp = Code::getTagPhp();
		$contextPhp = Code::getContextPhp($namespace, $uses);

		$builder = new MockBuilder();
		$functionPhp = $builder->getMockFunctionPhp($function);

		return Code::combine($tagPhp, $contextPhp, $functionPhp) . "\n";
	}
}
