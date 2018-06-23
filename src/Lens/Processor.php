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

namespace _Lens\Lens;

use _Lens\SpencerMortensen\ParallelProcessor\Fork\ForkProcess;
use _Lens\SpencerMortensen\ParallelProcessor\Processor as ParallelProcessor;
use _Lens\SpencerMortensen\ParallelProcessor\Shell\ShellClientProcess;
use _Lens\Lens\Jobs\Job;

class Processor extends ParallelProcessor
{
	/** @var boolean */
	private $useForks;

	public function __construct()
	{
		parent::__construct();

		$this->useForks = function_exists('pcntl_fork');
	}

	public function run(Job $job)
	{
		$process = $this->getProcess($job);
		$this->start($process);
	}

	public function getProcess(Job $job)
	{
		if ($this->useForks) {
			return new ForkProcess($job);
		}

		return new ShellClientProcess($job);
	}
}
