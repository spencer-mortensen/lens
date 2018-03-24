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

namespace Lens_0_0_56\Lens\Evaluator\Jobs;

use Lens_0_0_56\Lens\Evaluator\CacheBuilder;

class CacheJob implements Job
{
	/** @var string */
	private $executable;

	/** @var string */
	private $autoloadPath;

	/** @var string */
	private $cachePath;

	/** @var string */
	private $className;

	/** @var boolean */
	private $result;

	public function __construct($executable, $autoloadPath, $cachePath, $className, &$result)
	{
		$this->executable = $executable;
		$this->autoloadPath = $autoloadPath;
		$this->cachePath = $cachePath;
		$this->className = $className;
		$this->result = &$result;
	}

	public function getCommand()
	{
		$arguments = array($this->autoloadPath, $this->cachePath, $this->className);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --internal-cache={$encoded}";
	}

	public function start()
	{
		$cache = new CacheBuilder($this->autoloadPath, $this->cachePath);
		return $cache->run($this->className);
	}

	public function stop($message)
	{
		$this->result = $message;
	}
}
