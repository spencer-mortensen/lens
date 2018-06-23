<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of parallel-processor.
 *
 * Parallel-processor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Parallel-processor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with parallel-processor. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace _Lens\SpencerMortensen\ParallelProcessor\Fork;

use Exception;
use _Lens\SpencerMortensen\ParallelProcessor\ProcessorException;
use _Lens\SpencerMortensen\ParallelProcessor\ClientProcess;
use _Lens\SpencerMortensen\ParallelProcessor\Stream\Stream;
use _Lens\SpencerMortensen\ParallelProcessor\ServerProcess;
use Throwable;

class ForkProcess extends ServerProcess implements ClientProcess
{
	/** @var ForkJob */
	private $job;

	/** @var Stream */
	private $a;

	/** @var Stream */
	private $b;

	public function __construct(ForkJob $job)
	{
		parent::__construct($job);

		$this->job = $job;
	}

	public function start()
	{
		list($a, $b) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

		$this->a = new Stream($a);
		$this->b = new Stream($b);

		$pid = pcntl_fork();

		// Process
		if (0 < $pid) {
			$this->b->close();
			$this->a->setNonBlocking();
			return $a;
		}

		// Worker
		if ($pid === 0) {
			$this->run();
		}

		throw ProcessorException::forkError();
	}

	public function send($message)
	{
		try {
			$this->a->close();
			$this->b->write($message);
			$this->b->close();
		} catch (Throwable $throwable) {
			exit(1);
		} catch (Exception $exception) {
			exit(1);
		}

		exit(0);
	}

	public function stop($result)
	{
		$this->job->stop($result);
	}
}
