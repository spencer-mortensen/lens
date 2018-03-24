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
use Lens_0_0_56\Lens\Lock;
use Lens_0_0_56\SpencerMortensen\Paths\Paths;

abstract class CachedResource
{
	/** @var Paths  */
	protected $paths;

	/** @var Filesystem */
	protected $filesystem;

	/** @var Lock */
	protected $lock;

	public function __construct()
	{
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->lock = new Lock($this->getLockPath());
	}

	public function get($isLive)
	{
		if (!$this->lock->getShared()) {
			// TODO: throw exception
		}

		$path = $this->getPath($isLive);
		$exists = $this->filesystem->isFile($path);

		if ($exists) {
			// TODO: exception handling:
			include $path;
		}

		$this->lock->unlock();

		return $exists;
	}

	public function set()
	{
		if (!$this->lock->getExclusive()) {
			// TODO: throw exception
		}

		$this->setResource(true);
		$this->setResource(false);

		$this->lock->unlock();
	}

	private function setResource($isLive)
	{
		$path = $this->getPath($isLive);

		if ($this->filesystem->isFile($path)) {
			return;
		}

		$contents = $this->getCode($isLive);

		$this->filesystem->write($path, $contents);
	}

	private function getPath($isLive)
	{
		if ($isLive) {
			return $this->getLivePath();
		}

		return $this->getMockPath();
	}

	private function getCode($isLive)
	{
		if ($isLive) {
			return $this->getLiveCode();
		}

		return $this->getMockCode();
	}

	abstract protected function getLockPath();

	abstract protected function getLivePath();

	abstract protected function getMockPath();

	abstract protected function getLiveCode();

	abstract protected function getMockCode();
}
