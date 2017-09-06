<?php

namespace Lens\Engine\Shell;

use Exception;

abstract class Job
{
	/** @var integer */
	private static $STDOUT = 1;

	/** @var integer */
	private static $STDERR = 2;

	/** @var integer */
	private static $CHUNK_SIZE = 8192;

	/** @var string */
	private $command;

	/** @var null|resource */
	private $process;

	/** @var null|resource */
	private $stream;

	public function __construct($command)
	{
		$this->command = $command;
	}

	public function start(&$stream)
	{
		$descriptor = array(
			self::$STDOUT => array('pipe', 'w'),
			self::$STDERR => array('pipe', 'w')
		);

		$process = proc_open($this->command, $descriptor, $pipes);

		if (!is_resource($process)) {
			return false;
		}

		$stream = $pipes[self::$STDOUT];
		fclose($pipes[self::$STDERR]);

		$this->process = $process;
		$this->stream = $stream;

		return true;
	}

	public function stop()
	{
		$results = null;

		if (is_resource($this->stream)) {
			$results = self::read($this->stream);
			fclose($this->stream);
		}

		if (is_resource($this->process)) {
			proc_close($this->process);
		}

		return $results;
	}

	private static function read($stream)
	{
		for ($dataSerialized = ''; !feof($stream); $dataSerialized .= $chunk) {
			$chunk = fread($stream, self::$CHUNK_SIZE);

			if ($chunk === false) {
				throw new Exception('Unable to read from the socket stream');
			}
		}

		$output = unserialize($dataSerialized);

		return $output;
	}
}
