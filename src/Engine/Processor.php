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

namespace Lens\Engine;

use Exception;

class Processor
{
	/** @var integer */
	private static $CHUNK_SIZE = 8192;

	/** @var integer */
	private static $TIMEOUT_SECONDS = 3;

	/** @var integer */
	private static $TIMEOUT_MICROSECONDS = 0;

	/** @var array */
	private $input;

	/** @var array */
	private $output;

	/** @var resource */
	private $stream;

	/** @var null|integer */
	private $maximumJobs;

	/** @var integer */
	private $jobId;

	public function __construct($maximumJobs = null)
	{
		$this->maximumJobs = $maximumJobs;
		$this->jobId = 0;

		$this->input = array();
		$this->output = array();
	}

	public function run(Job $job, &$output)
	{
		if (count($this->input) === $this->maximumJobs) {
			$this->waitForResult();
		}

		list($a, $b) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

		if ($a === null) {
			throw new Exception('Unable to create a stream socket pair');
		}

		$processId = pcntl_fork();

		if ($processId < 0) {
			throw new Exception('Unable to fork the current process');
		}

		if (0 < $processId) {
			fclose($b);
			stream_set_blocking($a, false);

			$jobId = $this->jobId++;

			$this->input[$jobId] = $a;
			$this->output[$jobId] = &$output;
		} else {
			// TODO: this breaks programs that write to STDOUT/STDERR:
			fclose(STDOUT);
			fclose(STDERR);
			fclose($a);

			$callable = array($this, 'send');
			$this->stream = $b;

			$job->run($callable);
			exit;
		}
	}

	public function send($data)
	{
		self::write($this->stream, $data);
		fclose($this->stream);
	}

	public function halt()
	{
		while ($this->waitForResult());
	}

	private function waitForResult()
	{
		if (count($this->input) === 0) {
			return false;
		}

		$ready = $this->input;
		$x = null;

		if (stream_select($ready, $x, $x, self::$TIMEOUT_SECONDS, self::$TIMEOUT_MICROSECONDS) === 0) {
			throw new Exception('No jobs completed within the timeout period');
		}

		// TODO: The $jobId keys were lost prior to PHP 5.4
		foreach ($ready as $jobId => $stream) {
			$this->output[$jobId] = self::read($stream);
			fclose($stream);

			unset($this->input[$jobId], $this->output[$jobId]);
		}

		return true;
	}

	private static function read($stream)
	{
		for ($serializedData = ''; !feof($stream); $serializedData .= $chunk) {
			$chunk = fread($stream, self::$CHUNK_SIZE);

			if ($chunk === false) {
				throw new Exception('Unable to read from the socket stream');
			}
		}

		return unserialize($serializedData);
	}

	private static function write($stream, $data)
	{
		$serializedData = serialize($data);

		if (fwrite($stream, $serializedData) !== strlen($serializedData)) {
			throw new Exception('Unable to write to the socket stream');
		}
	}
}
