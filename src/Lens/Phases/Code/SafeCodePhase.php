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
use _Lens\Lens\Phases\Code\Parsers\FileParser;
use _Lens\Lens\Phases\Watcher;
use _Lens\Lens\Phases\Finder;
use _Lens\Lens\Php\Lexer;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;

class SafeCodePhase
{
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

	/** @var Lexer */
	private $lexer;

	/** @var Deflator */
	private $deflator;

	/** @var FileParser */
	private $fileParser;

	/** @var Definer */
	private $fileGenerator;

	// TODO: analyze the external libraries
	// TODO: handle PHP files with fatal syntax errors
	public function execute(Finder $finder, array &$meta)
	{
		$projectPath = $finder->getProjectPath();
		$srcPath = $finder->getSrcPath();
		$liveRelativePath = $projectPath->getRelativePath($srcPath);
		/** @var Path $indexPath */
		$indexPath = $finder->getIndexPath();
		$indexPath = $indexPath->add($liveRelativePath);
		$watcherPath = $indexPath->add('modified.json');
		$livePath = $finder->getLivePath();
		$mockPath = $finder->getMockPath();

		// TODO: construct these only when necessary:
		$lexer = new Lexer();
		$deflator = new Deflator();
		$fileParser = new FileParser();
		$fileGenerator = new Definer();

		$this->finder = $finder;
		$this->srcPath = $srcPath;
		$this->indexPath = $indexPath;
		$this->livePath = $livePath;
		$this->mockPath = $mockPath;
		$this->lexer = $lexer;
		$this->deflator = $deflator;
		$this->fileParser = $fileParser;
		$this->fileGenerator = $fileGenerator;

		$this->updateCache($watcherPath);
	}

	private function updateCache(Path $watcherPath)
	{
		$watcher = new Watcher();
		$srcDirectory = new Directory($this->srcPath);
		$watcherFile = new JsonFile($watcherPath);

		$changes = $watcher->watch($srcDirectory, $watcherFile);
		$this->updateDirectory([], $changes);
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

		if (!is_array($contents) || !is_array($contents['classes']) || !is_array($contents['functions'])) {
			return;
		}

		$livePath = $this->finder->getLivePath();
		$mockPath = $this->finder->getMockPath();
		$xdebugPath = $this->finder->getXdebugPath();

		foreach ($contents['classes'] as $class) {
			$path = $this->finder->getClassPath($livePath, $class);
			$file = new File($path);
			$file->delete();

			$path = $this->finder->getClassPath($mockPath, $class);
			$file = new File($path);
			$file->delete();

			unset($this->coverage['classes'][$class]);
		}

		foreach ($contents['functions'] as $function) {
			$path = $this->finder->getFunctionPath($livePath, $function);
			$file = new File($path);
			$file->delete();

			$path = $this->finder->getFunctionPath($mockPath, $function);
			$file = new File($path);
			$file->delete();

			$path = $this->finder->getFunctionPath($xdebugPath, $function);
			$file = new File($path);
			$file->delete();

			unset($this->coverage['functions'][$function]);
		}

		// TODO: clean up the "data" directory?
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

	private function add(File $srcFile, JsonFile $indexFile)
	{
		$php = $srcFile->read();

		if ($php === null) {
			return;
		}

		$inflatedTokens = $this->lexer->lex($php);
		$this->deflator->deflate($inflatedTokens, $deflatedTokens, $map);
		$input = new Input($deflatedTokens);

		if (!$this->fileParser->parse($input, $sections)) {
			return;
		}

		$filesPhp = $this->fileGenerator->getDefinitions($sections, $deflatedTokens, $inflatedTokens, $map);

		$livePath = $this->finder->getLivePath();
		$mockPath = $this->finder->getMockPath();
		$xdebugPath = $this->finder->getXdebugPath();

		foreach ($filesPhp['live']['classes'] as $name => $php) {
			$path = $this->finder->getClassPath($livePath, $name);
			$file = new File($path);
			$file->write($php);

			$this->coverage['classes'][$name] = null;
		}

		foreach ($filesPhp['live']['functions'] as $name => $php) {
			$path = $this->finder->getFunctionPath($livePath, $name);
			$file = new File($path);
			$file->write($php);

			$this->coverage['functions'][$name] = null;
		}

		foreach ($filesPhp['mock']['classes'] as $name => $php) {
			$path = $this->finder->getClassPath($mockPath, $name);
			$file = new File($path);
			$file->write($php);
		}

		foreach ($filesPhp['mock']['functions'] as $name => $php) {
			$path = $this->finder->getFunctionPath($mockPath, $name);
			$file = new File($path);
			$file->write($php);
		}

		foreach ($filesPhp['xdebug']['functions'] as $name => $php) {
			$path = $this->finder->getFunctionPath($xdebugPath, $name);
			$file = new File($path);
			$file->write($php);
		}

		$indexData = [
			'classes' => array_keys($filesPhp['live']['classes']),
			'functions' => array_keys($filesPhp['live']['functions'])
		];

		$indexFile->write($indexData);
	}
}
