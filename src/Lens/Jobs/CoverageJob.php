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

namespace _Lens\Lens\Jobs;

use _Lens\Lens\Tests\Autoloader;
use _Lens\Lens\Tests\StatementsExtractor;
use _Lens\SpencerMortensen\Filesystem\File;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class CoverageJob implements Job
{
	/** @var string */
	private $executable;

	/** @var Path */
	private $lensCore;

	/** @var Path */
	private $cache;

	/** @var Path */
	private $file;

	/** @var array|null */
	private $lineNumbers;

	public function __construct($executable, Path $lensCore, Path $cache, Path $file, array &$lineNumbers = null)
	{
		$this->executable = $executable;
		$this->lensCore = $lensCore;
		$this->cache = $cache;
		$this->file = $file;

		$this->lineNumbers = &$lineNumbers;
	}

	public function getCommand()
	{
		$arguments = [(string)$this->lensCore, (string)$this->cache, (string)$this->file];
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --internal-coverage={$encoded}";
	}

	public function start()
	{
		$mockClasses = [];
		$filesystem = new Filesystem();
		$autoloader = new Autoloader($filesystem, $this->lensCore, $this->cache, $mockClasses);
		$extractor = new StatementsExtractor($autoloader);
		$file = new File($this->file);

		return $extractor->getLineNumbers($file);
	}

	public function stop($message)
	{
		$this->lineNumbers = $message;
	}
}
