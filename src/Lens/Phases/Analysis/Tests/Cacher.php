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

namespace _Lens\Lens\Phases\Analysis\Tests;

use _Lens\Lens\DataFile;
use _Lens\Lens\JsonFile;
use _Lens\Lens\Phases\Analysis\Tests\Parser\Parser;
use _Lens\Lens\Phases\Analysis\Watcher;
use _Lens\SpencerMortensen\Filesystem\Directory;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Path;

class Cacher
{
	/** @var Parser|null */
	private $parser;

	public function cache(Path $liveTestsPath, Path $cachePath)
	{
		$cacheTestsPath = $cachePath->add('tests');
		$changes = $this->getChanges($liveTestsPath, $cacheTestsPath);

		$this->updateCacheDirectory($liveTestsPath, $cacheTestsPath, $changes);
	}

	private function getChanges(Path $liveTestsPath, Path $cacheTestsPath)
	{
		$liveTestsDirectory = new Directory($liveTestsPath);

		$watcherPath = $cacheTestsPath->add('modified.json');
		$watcherFile = new JsonFile($watcherPath);

		$watcher = new Watcher();
		return $watcher->watch($liveTestsDirectory, $watcherFile);
	}

	private function updateCacheDirectory(Path $live, Path $cached, array $differences)
	{
		foreach ($differences as $name => $value) {
			$liveChild = $live->add($name);
			$cachedChild = $cached->add($name);

			if (is_array($value)) {
				$this->updateCacheDirectory($liveChild, $cachedChild, $value);
			} else {
				$this->updateCacheFile($liveChild, $cachedChild, $value);
			}
		}
	}

	private function updateCacheFile(Path $livePath, Path $cachePath, $type)
	{
		$cacheFile = new DataFile($cachePath);

		if (($type === Watcher::TYPE_REMOVED) || ($type === Watcher::TYPE_MODIFIED)) {
			$cacheFile->delete();
		}

		if (($type === Watcher::TYPE_ADDED) || ($type === Watcher::TYPE_MODIFIED)) {
			$liveFile = new File($livePath);
			$this->addCacheFile($liveFile, $cacheFile);
		}
	}

	private function addCacheFile(File $liveFile, File $cacheFile)
	{
		if ($this->parser === null) {
			$this->parser = new Parser();
		}

		$text = $liveFile->read();
		$suite = $this->parser->parse($text);
		$cacheFile->write($suite);
	}
}
