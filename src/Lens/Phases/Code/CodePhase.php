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

namespace _Lens\Lens\Phases\Code;

use _Lens\Lens\JsonFile;
use _Lens\Lens\Phases\Code\Generators\Definer;
use _Lens\Lens\Phases\Watcher;
use _Lens\Lens\Phases\Finder;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Semantics;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;

class CodePhase
{
	/** @var Definer */
	private $definer;

	/** @var Finder */
	private $finder;

	/** @var Path */
	private $srcPath;

	/** @var Path */
	private $indexPath;

	/** @var Path */
	private $livePath;

	/** @var Path */
	private $mockPath;

	/** @var array */
	private $changedFunctions;

	/** @var array */
	private $generate;

	/** @var array */
	private $meta;

	public function __construct()
	{
		$this->definer = new Definer();
	}

	public function execute(Finder $finder, array &$meta)
	{
		$indexPath = $finder->getIndexPath();
		$projectPath = $finder->getProjectPath();
		$srcPath = $finder->getSrcPath();
		$liveRelativePath = $projectPath->getRelativePath($srcPath);
		$indexPath = $indexPath->add($liveRelativePath);

		$this->finder = $finder;
		$this->srcPath = $srcPath;
		$this->indexPath = $indexPath;
		$this->livePath = $this->finder->getLivePath();
		$this->mockPath = $this->finder->getMockPath();
		$this->changedFunctions = [];
		$this->generate = [];
		$this->meta = &$meta;

		$watcher = new Watcher();
		$srcDirectory = $this->getSrcDirectory();
		$watcherFile = $this->getWatcherFile();
		$changes = $watcher->watch($srcDirectory, $watcherFile);

		$this->updateDirectory([], $changes);
	}

	private function getSrcDirectory()
	{
		$srcPath = $this->finder->getSrcPath();
		return new Directory($srcPath);
	}

	private function getWatcherFile()
	{
		$watcherPath = $this->indexPath->add('modified.json');
		return new JsonFile($watcherPath);
	}

	private function updateDirectory(array $trail, array $changes)
	{
		foreach ($changes as $name => $type) {
			if (is_array($type)) {
				$childTrail = $trail;
				$childTrail[] = $name;

				$this->updateDirectory($childTrail, $type);
			} else {
				$this->updateFile($trail, $type);
			}
		}
	}

	private function updateFile(array $trail, $type)
	{
		$indexFile = $this->getIndexFile($trail);

		switch ($type) {
			case Watcher::TYPE_REMOVED:
				$this->remove($indexFile);
				return;

			case Watcher::TYPE_MODIFIED:
				$srcFile = $this->getSrcFile($trail);
				$this->remove($indexFile);
				$this->add($srcFile, $indexFile);
				return;

			case Watcher::TYPE_ADDED;
				$srcFile = $this->getSrcFile($trail);
				$this->add($srcFile, $indexFile);
				return;
		}
	}

	private function remove(JsonFile $indexFile)
	{
		$contents = $indexFile->read();
		$indexFile->delete();

		if ($contents === null) {
			return;
		}

		$this->deleteMetaData($contents);
		$this->deleteCode($this->livePath, $contents);
		$this->deleteCode($this->mockPath, $contents);
		$this->setChangedFunctions($contents);
	}

	private function deleteMetaData(array $contents)
	{
		foreach ($contents as $group => $names) {
			foreach ($names as $name) {
				unset($this->meta[$group][$name]);
			}
		}
	}

	private function deleteCode(Path $basePath, array $contents)
	{
		foreach ($contents['classes'] as $class) {
			$filePath = $this->finder->getClassPath($basePath, $class);
			$this->deleteFile($filePath);
		}

		foreach ($contents['functions'] as $function) {
			$filePath = $this->finder->getFunctionPath($basePath, $function);
			$this->deleteFile($filePath);
		}
	}

	private function deleteFile(Path $path)
	{
		$file = new File($path);
		$file->delete();
	}

	private function setChangedFunctions(array $contents)
	{
		foreach ($contents['functions'] as $name) {
			$this->changedFunctions[$name] = true;
		}
	}

	private function add(File $srcFile, JsonFile $indexFile)
	{
		$php = $srcFile->read();
		$definitions = $this->definer->getDefinitions($php);

		$this->writeIndexFile($definitions, $indexFile);
		$this->writeMetaData($definitions);
		$this->writeMocks($definitions);
		$this->rememberLive($definitions);
		$this->updateChangedFunctions($definitions);
	}

	private function writeIndexFile(array $definitions, JsonFile $indexFile)
	{
		$classes = array_merge(
			array_keys($definitions['classes']),
			array_keys($definitions['interfaces']),
			array_keys($definitions['traits'])
		);

		$functions = array_keys($definitions['functions']);

		$indexData = [
			'classes' => $classes,
			'functions' => $functions
		];

		$indexFile->write($indexData);
	}

	private function writeMetaData(array $definitions)
	{
		// TODO:
		// List all function dependencies, including any absolute paths, but skipping any conditional dependencies, and skipping any built-in functions which are already defined: ($paths, $context) => $dependencies
		// Remember to copy over any necessary "Lens" class or function live definition files...

		foreach ($definitions['classes'] as $name => $definition) {
			$context = $this->getCleanContext($definition['live']['context']);
			$php = $definition['live']['definition'];
			$dependencies = []; // TODO
			$conditions = $this->getConditions($context['functions']);

			$this->meta['classes'][$name] = [
				'context' => $context,
				'definition' => $php,
				'coverage' => null,
				'conditions' => $conditions,
				'dependencies' => $dependencies
			];
		}

		// TODO: functions, interfaces, traits
		exit;
	}

	private function getCleanContext(array $context)
	{
		$namespace = $context['namespace'];
		$classes = $this->getSafeClassAliases($context['classes']);
		$classes = $this->getNontrivialClassAliases($namespace, $classes);
		$functions = $this->getSafeFunctionAliases($context['functions']);
		$functions = $this->getNontrivialFunctionAliases($namespace, $functions);

		return [
			'namespace' => $namespace,
			'classes' => $classes,
			'functions' => $functions
		];
	}

	private function getSafeClassAliases(array $aliases)
	{
		foreach ($aliases as $alias => &$name) {
			if (Semantics::isUnsafeClass($name)) {
				$name = "Lens\\{$name}";
			}
		}

		return $aliases;
	}

	private function getNontrivialClassAliases($namespace, array $aliases)
	{
		foreach ($aliases as $alias => $name) {
			$aliasName = $this->getFullName($namespace, $alias);

			if ($name === $aliasName) {
				unset($aliases[$alias]);
			}
		}

		return $aliases;
	}

	private function getFullName($namespace, $alias)
	{
		if ($namespace === null) {
			return $alias;
		}

		return "{$namespace}\\{$alias}";
	}

	private function getSafeFunctionAliases(array $aliases)
	{
		foreach ($aliases as $alias => &$name) {
			if (is_array($name)) {
				$name = &$name[1];
			}

			if (Semantics::isUnsafeFunction($name)) {
				$name = "Lens\\{$name}";
			}
		}

		return $aliases;
	}

	private function getNontrivialFunctionAliases($namespace, array $aliases)
	{
		if ($namespace !== null) {
			return $aliases;
		}

		foreach ($aliases as $alias => $name) {
			if ($name === $alias) {
				unset($aliases[$alias]);
			}
		}

		return $aliases;
	}

	private function getConditions(array $functions)
	{
		$conditions = [];

		foreach ($functions as $function => $options) {
			if (is_array($options)) {
				$name = $options[0];
				$conditions[$name] = $name;
			}
		}

		return $conditions;
	}

	private function rememberLive(array $fileDefinitions)
	{
		foreach ($fileDefinitions as $group => $groupDefinitions) {
			foreach ($groupDefinitions as $name => $definition) {
				$this->generate[$group][$name] = $name;
			}
		}
	}

	private function writeMocks(array $fileDefinitions)
	{
		foreach ($fileDefinitions['classes'] as $name => $definition) {
			$path = $this->finder->getClassPath($this->mockPath, $name);
			$this->writeMock($definition['mock'], $path);
		}

		foreach ($fileDefinitions['functions'] as $name => $definition) {
			$path = $this->finder->getFunctionPath($this->mockPath, $name);
			$this->writeMock($definition['mock'], $path);
		}

		foreach ($fileDefinitions['traits'] as $name => $definition) {
			$path = $this->finder->getClassPath($this->mockPath, $name);
			$this->writeMock($definition['mock'], $path);
		}
	}

	private function writeMock(array $definition, Path $path)
	{
		list($context, $definitionPhp) = $definition;
		$contextPhp = $this->getContextPhp($context);
		$php = $this->getFilePhp($contextPhp, $definitionPhp);

		$file = new File($path);
		$file->write($php);
	}

	private function getContextPhp(array $context)
	{
		return Code::getFullContextPhp($context['namespace'], $context['classes'], $context['functions']);
	}

	private function getFilePhp($contextPhp, $definitionPhp)
	{
		$php = Code::combine($contextPhp, $definitionPhp);
		return Code::getFilePhp($php);
	}

	private function updateChangedFunctions(array $definitions)
	{
		foreach ($definitions['functions'] as $name => $definition) {
			if (isset($this->changedFunctions[$name])) {
				unset($this->changedFunctions[$name]);
			} else {
				$this->changedFunctions[$name] = true;
			}
		}
	}

	private function getSrcFile(array $trail)
	{
		$path = call_user_func_array([$this->srcPath, 'add'], $trail);
		return new File($path);
	}

	private function getIndexFile(array $trail)
	{
		$path = call_user_func_array([$this->indexPath, 'add'], $trail);
		return new JsonFile($path);
	}
}
