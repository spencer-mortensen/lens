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

namespace _Lens\Lens\Analyzer\Code;

use _Lens\Lens\Analyzer\Code\Sanitizer\LiveGenerator;
use _Lens\Lens\Analyzer\Code\Sanitizer\MockClassGenerator;
use _Lens\Lens\Analyzer\Code\Sanitizer\MockFunctionGenerator;
use _Lens\Lens\Analyzer\Watcher;
use _Lens\Lens\JsonFile;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;

class Cacher
{
	/** @var Path */
	private $srcPath;

	/** @var Path */
	private $indexPath;

	/** @var Path */
	private $codePath;

	/** @var Processor */
	private $processor;

	/** @var LiveGenerator */
	private $liveGenerator;

	/** @var MockClassGenerator */
	private $mockClassGenerator;

	/** @var MockFunctionGenerator */
	private $mockFunctionGenerator;

	public function cache(Path $projectPath, Path $srcPath, Path $cachePath)
	{
		$codePath = $cachePath->add('code');
		$indexPath = $codePath->add('index');
		$watcherPath = $indexPath->add('modified.json');
		$liveRelativePath = $projectPath->getRelativePath($srcPath);

		$this->srcPath = $srcPath;
		$this->indexPath = $indexPath->add($liveRelativePath);
		$this->codePath = $codePath;

		// TODO: instantiate these objects only when necessary:
		$this->processor = new Processor();
		$this->liveGenerator = new LiveGenerator();
		$this->mockClassGenerator = new MockClassGenerator();
		$this->mockFunctionGenerator = new MockFunctionGenerator();

		//////

		$changes = $this->getChanges($watcherPath);

		$trail = [];
		$this->updateDirectory($trail, $changes);
	}

	private function getChanges(Path $watcherPath)
	{
		$watcher = new Watcher();
		$srcDirectory = new Directory($this->srcPath);
		$watcherFile = new JsonFile($watcherPath);

		return $watcher->watch($srcDirectory, $watcherFile);
	}

	private function updateDirectory(array $trail, array $changes)
	{
		foreach ($changes as $name => $type) {
			$childTrail = $trail;
			$childTrail[] = $name;

			if (is_array($type)) {
				$this->updateDirectory($childTrail, $type);
			} else {
				$this->updateFile($childTrail, $type);
			}
		}
	}

	private function updateFile(array $trail, $type)
	{
		array_pop($trail);

		switch ($type) {
			case Watcher::TYPE_REMOVED:
				$this->remove($trail);
				break;

			case Watcher::TYPE_MODIFIED:
				$this->remove($trail);
				$this->add($trail);
				break;

			case Watcher::TYPE_ADDED;
				$this->add($trail);
				break;
		}
	}

	private function remove(array $trail)
	{
		echo "removing: ", json_encode($trail), "\n";
	}

	private function add(array $trail)
	{
		$definitions = $this->readDefinitions($trail);

		$this->writeIndex($trail, $definitions);
		$this->writeCode($definitions);
	}

	private function readDefinitions(array $trail)
	{
		// TODO: extend the "add" method to accept arrays:
		$path = call_user_func_array([$this->srcPath, 'add'], $trail);
		$file = new File($path);
		$php = $file->read();

		return $this->processor->parse($php);
	}

	private function writeIndex(array $trail, array $definitions)
	{
		$contents = $this->getIndexContents($definitions);

		// TODO: replace the final ".php" with ".json"
		// TODO: extend the "add" method to accept arrays:
		$path = call_user_func_array([$this->indexPath, 'add'], $trail);
		$file = new JsonFile($path);
		$file->write($contents);
	}

	private function getIndexContents(array $definitions)
	{
		$functions = [];
		$classes = [];

		foreach ($definitions as $name => $definition) {
			$type = $definition['type'];

			if ($type === 'function') {
				$functions[] = $name;
			} else {
				$classes[] = $name;
			}
		}

		return [
			'functions' => $functions,
			'classes' => $classes
		];
	}

	private function writeCode(array $definitions)
	{
		$codePathString = (string)$this->codePath;

		foreach ($definitions as $name => $definition) {
			$relativePathString = strtr($name, '\\', '/');

			$dataPath = "{$codePathString}/data/{$relativePathString}.json";
			$this->writeData($dataPath, $definition);

			// $userBase = "{$codePathString}/user/{$relativePathString}";
			// $this->writeUserCode($userBase, $definition);

			$liveBase = "{$codePathString}/live/{$relativePathString}";
			$this->writeLiveCode($liveBase, $definition);

			$mockBase = "{$codePathString}/mock/{$relativePathString}";
			$this->writeMockCode($mockBase, $definition);
		}
	}

	private function writeData($pathString, array $definition)
	{
		$path = Path::fromString($pathString);
		$file = new JsonFile($path);
		$file->write($definition);
	}

	private function writeUserCode($pathString, array $definition)
	{
		$file = $this->getCodeFile($pathString, $definition);
		$contents = $this->getUserCodeContents($definition);

		$file->write($contents);
	}

	private function getCodeFile($pathString, array $definition)
	{
		if ($definition['type'] === 'function') {
			$pathString .= '.function';
		}

		$pathString .= '.php';

		$path = Path::fromString($pathString);
		return new File($path);
	}

	private function getUserCodeContents(array $definition)
	{
		$namespace = $definition['namespace'];
		$classAliases = $this->getUserAliases($namespace, $definition['classes']);
		$functionAliases = $this->getUserAliases($namespace, $definition['functions']);

		$contextPhp = self::getContextPhp($namespace, $classAliases, $functionAliases);
		$definitionPhp = $definition['definition'];

		$php = self::combine($contextPhp, $definitionPhp);
		return self::getFilePhp($php);
	}

	private function getUserAliases($namespace, array $aliases)
	{
		if ($namespace !== null) {
			return $aliases;
		}

		$safe = [];

		foreach ($aliases as $alias => $name) {
			if ($alias === $name) {
				continue;
			}

			$safe[$alias] = $name;
		}

		return $safe;
	}

	private function writeLiveCode($pathString, array $definition)
	{
		$file = $this->getCodeFile($pathString, $definition);
		$contents = $this->liveGenerator->generate($definition['context'], $definition['tokens']);

		$file->write($contents);
	}

	private function writeMockCode($pathString, array $definition)
	{
		$file = $this->getCodeFile($pathString, $definition);

		if ($definition['type'] === 'function') {
			$contents = $this->mockFunctionGenerator->generate($definition['context']['namespace'], $definition['tokens']);
		} elseif ($definition['type'] === 'class') {
			$contents = $this->mockClassGenerator->generate($definition['context']['namespace'], $definition['tokens']);
		} else {
			$contents = 'TODO';
		}

		$file->write($contents);
	}
}
