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

namespace Lens_0_0_56\Lens\Jobs;

use Lens_0_0_56\Lens\Evaluator\CacheBuilder;

class CacheJob implements Job
{
	/** @var string */
	private $executable;

	/** @var string */
	private $project;

	/** @var string */
	private $src;

	/** @var string */
	private $autoload;

	/** @var string */
	private $cache;

	/** @var array */
	private $mockFunctions;

	public function __construct($executable, $project, $src, $autoload, $cache, array $mockFunctions)
	{
		$this->executable = $executable;
		$this->project = $project;
		$this->src = $src;
		$this->autoload = $autoload;
		$this->cache = $cache;
		$this->mockFunctions = $mockFunctions;
	}

	public function getCommand()
	{
		$arguments = array($this->project, $this->src, $this->autoload, $this->cache, $this->mockFunctions);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --internal-cache={$encoded}";
	}

	public function start()
	{
		$builder = new CacheBuilder($this->project, $this->src, $this->autoload, $this->cache, $this->mockFunctions);
		$builder->build();
	}

	public function stop($message)
	{
	}
}
