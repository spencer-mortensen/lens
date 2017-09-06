<?php

namespace Lens\Engine\Shell;

use Exception;

class Processor
{
	/** @var integer */
	private static $TIMEOUT_SECONDS = 3;

	/** @var integer */
	private static $TIMEOUT_MICROSECONDS = 0;

	/** @var integer */
	private $jobId;

	/** @var Job[] */
	private $jobs;

	/** @var resource[] */
	private $streams;

	public function __construct()
	{
		$this->jobId = 0;
		$this->jobs = array();
		$this->streams = array();
	}

	public function start(Job $job)
	{
		if (!$job->start($stream)) {
			return false;
		}

		// TODO:
		stream_set_blocking($stream, false);

		$jobId = $this->jobId++;

		$this->jobs[$jobId] = $job;
		$this->streams[$jobId] = $stream;

		return true;
	}

	public function halt()
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

		foreach ($ready as $jobId => $stream) {
			$job = $this->jobs[$jobId];
			$job->stop();

			unset($this->jobs[$jobId], $this->streams[$jobId]);
		}

		return true;
	}
}
