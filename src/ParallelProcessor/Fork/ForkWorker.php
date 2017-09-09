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
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace SpencerMortensen\ParallelProcessor\Fork;

use Exception;
use SpencerMortensen\ParallelProcessor\Stream;
use SpencerMortensen\ParallelProcessor\Worker;

class ForkWorker implements Worker
{
	/** @var ForkJob */
	private $job;

	/** @var null|Stream */
	private $stream;

	public function __construct(ForkJob $job)
	{
		$this->job = $job;
	}

	public function run()
	{
		list($a, $b) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

		if ($a === null) {
			throw new Exception('Unable to create a stream socket pair');
		}

		$pid = pcntl_fork();

		// Worker process
		if ($pid === 0) {
			fclose($a);

			$stream = new Stream($b);

			$send = function ($message) use ($stream) {
				$stream->write($message);
				$stream->close();
			};

			$this->job->run($send);
			exit(0);
		}

		// Master process
		if (0 < $pid) {
			fclose($b);

			stream_set_blocking($a, false);
			$this->stream = new Stream($a);

			return $a;
		}

		throw new Exception('Unable to fork the current process');
	}

	public function receive($message)
	{
		$this->job->receive($message);
	}
}
