<?php

namespace Lens\Engine\Shell\Jobs;

use Lens\Engine\Shell\Job;

class ActualJob extends Job
{
	/** @var null|array */
	private $results;

	/** @var null|array */
	private $coverage;

	public function __construct($executable, $lensDirectory, $srcDirectory, $fixture, $input, $output, $subject, &$results, &$coverage)
	{
		$command = $this->getCommand($executable, $lensDirectory, $srcDirectory, $fixture, $input, $output, $subject);

		parent::__construct($command);

		$this->results = &$results;
		$this->coverage = &$coverage;
	}

	public function stop()
	{
		$output = parent::stop();

		$this->results = $output['state'];
		$this->coverage = $output['coverage'];
	}

	private function getCommand($executable, $lensDirectory, $srcDirectory, $fixture, $input, $output, $subject)
	{
		$arguments = array($lensDirectory, $srcDirectory, $fixture, $input, $output, $subject);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$executable} --actual={$encoded}";
	}
}
