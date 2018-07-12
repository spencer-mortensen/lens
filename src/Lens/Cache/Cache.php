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

namespace _Lens\Lens\Cache;

use _Lens\Lens\Citations;
use _Lens\Lens\Coverage;
use _Lens\Lens\SourcePaths;
use _Lens\Lens\Paragraph;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Namespacing;
use _Lens\Lens\Sanitizer;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;
use ReflectionClass;
use ReflectionFunction;

/*

Assumptions:

 * The user autoloader uses "require_once" or "include_once" (rather than "require" or "include")
 * The "src" directory contains only source code (no controller scripts, or constant definitions, or global variables)
 * The "src" directory contains scripts that have no fatal syntax errors (i.e. all files must all be parseable)
 * The source files use the common namespace format (not the brace format) with parseable method calls

*/

class Cache
{
	/** @var Path */
	private $project;

	/** @var Path */
	private $src;

	/** @var Path */
	private $autoload;

	/** @var Watcher */
	private $watcher;

	/** @var Citations */
	private $citations;

	/** @var Coverage */
	private $coverage;

	/** @var FileParser */
	private $parser;

	/** @var Sanitizer */
	private $sanitizer;

	/** @var SourcePaths */
	private $sourcePaths;

	/** @var array */
	private $scannedDirectories;

	/** @var array */
	private $modifiedFiles;

	public function __construct(Path $core, Path $project, Path $src, Path $autoload, Path $cache, array $mockFunctions)
	{
		$namespacing = new Namespacing('function_exists');

		$this->project = $project;
		$this->src = $src;
		$this->autoload = $autoload;
		$this->watcher = $this->getWatcher($cache);
		$this->citations = new Citations($cache);
		$this->coverage = new Coverage($cache);
		$this->parser = new FileParser();
		$this->sanitizer = new Sanitizer($namespacing, $mockFunctions);
		$this->sourcePaths = new SourcePaths($core, $cache);
	}

	private function getWatcher(Path $cache)
	{
		$filesystem = new Filesystem();
		// TODO: absorb this into the "Watcher"?
		$path = $cache->add('timestamps.json');
		return new Watcher($filesystem, $path, $this->project);
	}

	public function build()
	{
		$this->scannedDirectories = [];
		$this->modifiedFiles = [];

		$directories = [];

		$srcDirectory = new Directory($this->src);
		$this->getChildDirectories($srcDirectory, $directories);

		$declarations = new Declarations();
		$declarations->start();

		if ($this->watcher->isModifiedFilePath($this->autoload)) {
			$this->modifiedFiles[(string)$this->autoload] = true;
		}

		$this->requireOnce((string)$this->autoload);

		foreach ($directories as $directory) {
			$this->scanDirectory($directory);
		}

		while ($declarations->get($filePathString, $classes, $functions, $interfaces, $traits)) {
			$filePath = Path::fromString($filePathString);
			$file = new File($filePath);

			if ($filePathString !== (string)$this->autoload) {
				$parentPath = $filePath->add('..');
				$parent = new Directory($parentPath);

				$this->scanDirectory($parent);
			}

			$this->addFile($file, $classes, $functions, $interfaces, $traits);
		}
	}

	private function getChildDirectories(Directory $directory, array &$directories)
	{
		$directories[] = $directory;

		$children = $directory->read();

		foreach ($children as $child) {
			if ($child instanceof Directory) {
				$this->getChildDirectories($child, $directories);
			}
		}
	}

	private function scanDirectory(Directory $directory)
	{
		$directoryPath = $directory->getPath();
		$scannedDirectory = &$this->scannedDirectories[(string)$directoryPath];

		if ($scannedDirectory !== null) {
			return;
		}

		$changes = $this->watcher->getDirectoryChanges($directoryPath);

		foreach ($changes as $fileString => $exists) {
			if (!$this->isPhpFile($fileString)) {
				continue;
			}

			// If the file is modified or deleted:
			$this->removeFile($fileString);

			// If the file is new or modified:
			if ($exists) {
				$this->requireOnce($fileString);

				$this->modifiedFiles[$fileString] = true;
			}
		}

		$scannedDirectory = true;
	}

	// TODO: this is duplicated elsewhere
	private function isPhpFile($path)
	{
		return substr($path, -4) === '.php';
	}

	private function removeFile($absolutePath)
	{
		$relativePath = (string)$this->project->getRelativePath($absolutePath);

		$this->citations->remove($relativePath, $classes, $functions, $interfaces, $traits);

		foreach ($classes as $class) {
			$this->removeClass($class);
		}

		foreach ($functions as $function) {
			$this->removeFunction($function);
		}

		foreach ($interfaces as $interface) {
			$this->removeInterface($interface);
		}

		foreach ($traits as $trait) {
			$this->removeTrait($trait);
		}
	}

	private function removeClass($class)
	{
		$this->coverage->unsetClass($class);

		$liveClassPath = $this->sourcePaths->getLiveClassPath($class);
		$this->deleteFile($liveClassPath);

		$mockClassPath = $this->sourcePaths->getMockClassPath($class);
		$this->deleteFile($mockClassPath);
	}

	private function deleteFile(Path $path)
	{
		$file = new File($path);
		$file->delete();
	}

	private function removeFunction($function)
	{
		$this->coverage->unsetFunction($function);

		$liveFunctionPath = $this->sourcePaths->getLiveFunctionPath($function);
		$this->deleteFile($liveFunctionPath);

		$mockFunctionPath = $this->sourcePaths->getMockFunctionPath($function);
		$this->deleteFile($mockFunctionPath);
	}

	private function removeInterface($interface)
	{
		$interfacePath = $this->sourcePaths->getInterfacePath($interface);
		$this->deleteFile($interfacePath);
	}

	private function removeTrait($trait)
	{
		$this->coverage->unsetTrait($trait);

		$liveTraitPath = $this->sourcePaths->getLiveTraitPath($trait);
		$this->deleteFile($liveTraitPath);

		$mockTraitPath = $this->sourcePaths->getMockTraitPath($trait);
		$this->deleteFile($mockTraitPath);
	}

	private function requireOnce($fileString)
	{
		// TODO: exception handling
		require_once $fileString;
	}

	private function addFile(File $file, array $classes, array $functions, array $interfaces, array $traits)
	{
		if (!$this->isModifiedFile($file)) {
			return;
		}

		$filePhp = $file->read();
		$filePhp = Paragraph::standardizeNewlines($filePhp);

		list($namespace, $uses) = $this->parser->parse($filePhp);

		// TODO: What if  the $path is not a fully-qualified class name? (Maybe it's a partial namespace path?)
		// TODO: Support the function and constant use-statements: http://php.net/manual/en/language.namespaces.importing.php
		foreach ($uses as $alias => $path) {
			class_exists($path, true);
		}

		$absolutePath = (string)$file->getPath();
		$relativePath = (string)$this->project->getRelativePath($absolutePath);

		foreach ($classes as $class) {
			$this->citations->addClass($class, $relativePath);
			$this->coverage->setClass($class, null);

			$this->addLiveClass($class, $namespace, $uses, $filePhp);
			$this->addMockClass($class);
		}

		foreach ($functions as $function) {
			$this->citations->addFunction($function, $relativePath);
			$this->coverage->setFunction($function, null);

			$this->addLiveFunction($function, $namespace, $uses, $filePhp);
			$this->addMockFunction($function);
		}

		foreach ($interfaces as $interface) {
			$this->citations->addInterface($interface, $relativePath);

			$this->addInterface($interface, $namespace, $uses, $filePhp);
		}

		foreach ($traits as $trait) {
			$this->citations->addTrait($trait, $relativePath);
			$this->coverage->setTrait($trait, null);

			$this->addLiveTrait($trait, $namespace, $uses, $filePhp);
			$this->addMockTrait($trait);
		}
	}

	private function isModifiedFile(File $file)
	{
		$filePathString = (string)$file->getPath();
		return isset($this->modifiedFiles[$filePathString]);
	}

	private function addLiveClass($class, $namespace, array $uses, $filePhp)
	{
		$path = $this->sourcePaths->getLiveClassPath($class);

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
		$lines = explode("\n", $filePhp);

		$begin = $reflection->getStartLine() - 1;
		$length = $reflection->getEndLine() - $begin;

		$lines = array_slice($lines, $begin, $length);
		return implode("\n", $lines);
	}

	private function write($namespace, array $uses, $definitionPhp, Path $path)
	{
		$tagPhp = Code::getTagPhp();
		$contextPhp = Code::getContextPhp($namespace, $uses);
		$filePhp = Code::combine($tagPhp, $contextPhp, $definitionPhp) . "\n";

		$file = new File($path);
		$file->write($filePhp);
	}

	private function addMockClass($class)
	{
		$path = $this->sourcePaths->getMockClassPath($class);
		$code = $this->getMockClassCode($class);

		$file = new File($path);
		$file->write($code);
	}

	private function getMockClassCode($class)
	{
		// TODO: absorb this into the "MockBuilder"
		$reflection = new ReflectionClass($class);
		$namespace = $reflection->getNamespaceName();

		if (strlen($namespace) === 0) {
			$namespace = null;
		}

		$uses = [
			'Agent' => $this->getAgentNamespace()
		];

		$tagPhp = Code::getTagPhp();
		$contextPhp = Code::getContextPhp($namespace, $uses);

		$builder = new MockBuilder();
		$classPhp = $builder->getMockClassPhp($class);

		return Code::combine($tagPhp, $contextPhp, $classPhp) . "\n";
	}

	private function getAgentNamespace()
	{
		// TODO: this is fragile:
		$names = explode('\\', __NAMESPACE__);
		array_pop($names);
		$names[] = 'Tests';
		$names[] = 'Agent';

		return implode('\\', $names);
	}

	private function addLiveFunction($function, $namespace, array $uses, $filePhp)
	{
		$path = $this->sourcePaths->getLiveFunctionPath($function);

		$reflection = new ReflectionFunction($function);
		$functionPhp = $this->getDefinitionPhp($reflection, $filePhp);
		$functionPhp = $this->sanitizer->sanitize('function', $namespace, $uses, $functionPhp);

		$this->write($namespace, $uses, $functionPhp, $path);
	}

	private function addMockFunction($function)
	{
		$path = $this->sourcePaths->getMockFunctionPath($function);
		$code = $this->getMockFunctionCode($function);

		$file = new File($path);
		$file->write($code);
	}

	private function getMockFunctionCode($function)
	{
		// TODO: absorb this into the "MockBuilder"
		$reflection = new ReflectionFunction($function);
		$namespace = $reflection->getNamespaceName();

		$uses = [
			'Agent' => $this->getAgentNamespace()
		];

		$tagPhp = Code::getTagPhp();
		$contextPhp = Code::getContextPhp($namespace, $uses);

		$builder = new MockBuilder();
		$functionPhp = $builder->getMockFunctionPhp($function);

		return Code::combine($tagPhp, $contextPhp, $functionPhp) . "\n";
	}

	private function addInterface($interface, $namespace, array $uses, $filePhp)
	{
		$path = $this->sourcePaths->getInterfacePath($interface);

		$reflection = new ReflectionClass($interface);
		$interfacePhp = $this->getDefinitionPhp($reflection, $filePhp);

		$this->write($namespace, $uses, $interfacePhp, $path);
	}

	private function addLiveTrait($trait, $namespace, array $uses, $filePhp)
	{
		$path = $this->sourcePaths->getLiveTraitPath($trait);

		$reflection = new ReflectionClass($trait);
		$traitPhp = $this->getDefinitionPhp($reflection, $filePhp);
		$traitPhp = $this->sanitizer->sanitize('trait', $namespace, $uses, $traitPhp);

		$this->write($namespace, $uses, $traitPhp, $path);
	}

	private function addMockTrait($trait)
	{
		$path = $this->sourcePaths->getMockTraitPath($trait);
		$code = $this->getMockClassCode($trait);

		$file = new File($path);
		$file->write($code);
	}
}
