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

namespace _Lens\Lens\Commands;

use _Lens\Lens\Arguments;
use _Lens\Lens\Jobs\TestJob;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\ParallelProcessor\Shell\ShellServerProcess;

class LensTest implements Command
{
	/** @var Arguments */
	private $arguments;

	public function __construct(Arguments $arguments)
	{
		$this->arguments = $arguments;
	}

	public function run(&$stdout = null, &$stderr = null, &$exitCode = null)
	{
		$options = $this->arguments->getOptions();
		$input = &$options['internal-test'];

		if ($input === null) {
			return false;
		}

		// TODO: if there are any other options, or any other values, then throw a usage exception

		$decoded = base64_decode($input);
		$decompressed = gzinflate($decoded);
		$arguments = unserialize($decompressed);

		$executable = $this->arguments->getExecutable();
		list($coreString, $cacheString, $contextPhp, $prePhp, $postPhp, $script, $mockClasses, $isActual) = $arguments;

		// TODO: dependency injection?
		$filesystem = new Filesystem();
		$core = $filesystem->getPath($coreString);
		$cache = $filesystem->getPath($cacheString);

		$job = new TestJob($executable, $core, $cache, $contextPhp, $prePhp, $postPhp, $script, $mockClasses, $isActual, $process, $preState, $postState, $coverage);
		$process = new ShellServerProcess($job);

		$process->run();
		return true;
	}
}
