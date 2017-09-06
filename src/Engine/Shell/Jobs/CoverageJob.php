<?php

namespace Lens\Engine\Shell\Jobs;

use Lens\Engine\Shell\Job;

class CoverageJob extends Job
{
	/** @var array */
	private $code;

	/** @var array */
	private $coverage;

	public function __construct($executable, $srcDirectory, array $relativePaths, &$code, &$coverage)
	{
		$command = $this->getCommand($executable, $srcDirectory, $relativePaths);

		parent::__construct($command);

		$this->code = &$code;
		$this->coverage = &$coverage;
	}

	public function stop()
	{
		list($this->code, $this->coverage) = parent::stop();
	}

	private function getCommand($executable, $srcDirectory, array $relativePaths)
	{
		$arguments = array($srcDirectory, $relativePaths);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$executable} --coverage={$encoded}";
	}
}
