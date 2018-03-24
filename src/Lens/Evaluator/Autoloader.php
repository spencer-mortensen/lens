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

use Lens_0_0_56\Lens\Evaluator\Jobs\CacheJob;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\Lens\Lock;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Processor;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Shell\ShellClientProcess;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

class Autoloader
{
	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var string */
	private $executable;

	/** @var string */
	private $autoload;

	/** @var string */
	private $cache;

	/** @var array */
	private $liveClasses;

	/** @var array */
	private $map;

	/** @var Processor */
	private $processor;

	public function __construct(Paths $paths, Filesystem $filesystem, $executable, $autoload, $cache, array $liveClasses)
	{
		$this->paths = $paths;
		$this->filesystem = $filesystem;
		$this->executable = $executable;
		$this->autoload = $autoload;
		$this->cache = $cache;
		$this->liveClasses = $liveClasses;

		///

		$this->map = $this->getMap();
		$this->processor = new Processor();

		spl_autoload_register(array($this, 'autoload'));
	}

	public function __destruct()
	{
		$mapPath = $this->getMapPath();
		$json = json_encode($this->map);

		$this->filesystem->write($mapPath, $json);
	}

	private function getMap()
	{
		$mapPath = $this->getMapPath();

		$json = $this->filesystem->read($mapPath);
		$map = json_decode($json, true);

		if (!is_array($map)) {
			$map = array();
		}

		return $map;
	}

	private function getMapPath()
	{
		return $this->paths->join($this->cache, 'map.json');
	}

	public function autoload($class)
	{
		$cache = new CachedClass($this->cache, $class);

		$isLive = $this->isLiveClass($class);

		if ($cache->get($isLive)) {
			return;
		}

		$relativePath = strtr($class, '\\', '.') . '.lock';
		$lockPath = $this->paths->join($this->cache, 'locks', 'process', $relativePath);
		$lock = new Lock($lockPath);

		if (!$lock->getExclusive()) {
			// TODO: throw exception
		}

		if ($cache->get($isLive)) {
			$lock->unlock();
			return;
		}

		$job = new CacheJob($this->executable, $this->autoload, $this->cache, $class, $result);
		$process = new ShellClientProcess($job);
		$this->processor->start($process);
		$this->processor->finish();

		$lock->unlock();

		$cache->get($isLive);
	}

	private function isLiveClass($class)
	{
		return isset($this->liveClasses[$class]);
	}
}
