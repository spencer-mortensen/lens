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

namespace Lens_0_0_56\SpencerMortensen\ParallelProcessor;

use Lens_0_0_56\SpencerMortensen\ParallelProcessor\Stream\Stream;

class Processor
{
	/** @var integer */
	private static $TIMEOUT_SECONDS = 3;

	/** @var integer */
	private static $TIMEOUT_MICROSECONDS = 0;

	/** @var integer */
	private $id;

	/** @var ClientProcess[] */
	private $processes;

	/** @var resource[] */
	private $streams;

	public function __construct()
	{
		$this->id = 0;
		$this->processes = array();
		$this->streams = array();
	}

	public function start(ClientProcess $process)
	{
		$stream = $process->start();

		$this->processes[$this->id] = $process;
		$this->streams[$this->id] = $stream;

		++$this->id;
	}

	public function finish()
	{
		while ($this->waitForResult());
	}

	private function waitForResult()
	{
		if (count($this->streams) === 0) {
			return false;
		}

		$ready = $this->streams;
		$x = null;

		if (stream_select($ready, $x, $x, self::$TIMEOUT_SECONDS, self::$TIMEOUT_MICROSECONDS) === 0) {
			throw ProcessorException::timeout();
		}

		foreach ($ready as $id => $resource) {
			$stream = new Stream($resource);
			$message = $stream->read();
			$stream->close();

			$result = Message::deserialize($message);

			$process = $this->processes[$id];
			$process->stop($result);

			unset($this->processes[$id], $this->streams[$id]);
		}

		return true;
	}
}
