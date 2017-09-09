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

namespace SpencerMortensen\ParallelProcessor;

use Exception;

class Processor
{
	/** @var integer */
	private static $TIMEOUT_SECONDS = 3;

	/** @var integer */
	private static $TIMEOUT_MICROSECONDS = 0;

	/** @var integer */
	private $id;

	/** @var Worker[] */
	private $workers;

	/** @var resource[] */
	private $streams;

	public function __construct()
	{
		$this->id = 0;
		$this->workers = array();
		$this->streams = array();
	}

	public function run(Worker $worker)
	{
		$stream = $worker->run();

		$id = $this->id++;

		$this->workers[$id] = $worker;
		$this->streams[$id] = $stream;
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
			throw new Exception('No jobs completed within the timeout period');
		}

		foreach ($ready as $id => $resource) {
			$stream = new Stream($resource);
			$message = $stream->read();
			fclose($resource);

			$worker = $this->workers[$id];
			$worker->receive($message);

			unset($this->workers[$id], $this->streams[$id]);
		}

		return true;
	}
}
