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

namespace Lens_0_0_56\Lens\Cache;

use Lens_0_0_56\Lens\Finder;
use Lens_0_0_56\Lens\Jobs\CacheJob;
use Lens_0_0_56\Lens\Processor;

class CacheBuilder
{
	/** @var string */
	private $executable;

	/** @var Finder */
	private $finder;

	public function __construct($executable, Finder $finder)
	{
		$this->executable = $executable;
		$this->finder = $finder;
	}

	public function run(array $mockFunctions)
	{
		$lens = $this->finder->getLens();

		if ($lens === null) {
			return;
		}

		$sourceJob = new CacheJob(
			$this->executable,
			$this->finder->getCore(),
			$this->finder->getProject(),
			$this->finder->getSrc(),
			$this->finder->getAutoload(),
			$this->finder->getCache(),
			$mockFunctions
		);

		$processor = new Processor();
		$processor->run($sourceJob);
		$processor->finish();
	}
}
