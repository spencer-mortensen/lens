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

namespace Lens;

use Lens\Evaluator\Jobs\CoverageJob;
use Lens\Evaluator\Jobs\TestJob;
use SpencerMortensen\ParallelProcessor\Shell\ShellSlave;

class Worker
{
	/** @var string */
	private $executable;

	public function __construct($executable)
	{
		$this->executable = $executable;
	}

	public function run($jobName, $jobData)
	{
		$decoded = base64_decode($jobData);
		$decompressed = gzinflate($decoded);
		$arguments = unserialize($decompressed);

		switch ($jobName) {
			case 'coverage':
				list($srcDirectory, $relativePaths, $autoloadPath) = $arguments;
				$job = new CoverageJob($this->executable, $srcDirectory, $relativePaths, $autoloadPath, $code, $coverage);
				break;

			case 'test':
				list($lensDirectory, $srcDirectory, $autoloadPath, $contextPhp, $beforePhp, $afterPhp, $script) = $arguments;
				$job = new TestJob($this->executable, $lensDirectory, $srcDirectory, $autoloadPath, $contextPhp, $beforePhp, $afterPhp, $script, $preState, $postState, $coverage);
				break;

			default:
				// TODO: error
				return null;
		}

		$slave = new ShellSlave($job);
		$slave->run();
	}
}
