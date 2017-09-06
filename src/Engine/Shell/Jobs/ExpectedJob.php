<?php

namespace Lens\Engine\Shell\Jobs;

use Lens\Engine\Shell\Job;

class ExpectedJob extends Job
{
	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var null|string */
	private $script;

	public function __construct($executable, $lensDirectory, $fixture, $input, $output, &$preState, &$postState, &$script)
	{
		$command = $this->getCommand($executable, $lensDirectory, $fixture, $input, $output);

		parent::__construct($command);

		$this->preState = &$preState;
		$this->postState = &$postState;
		$this->script = &$script;
	}

	public function stop()
	{
		list($this->preState, $this->postState, $this->script) = parent::stop();
	}

	private function getCommand($executable, $lensDirectory, $fixture, $input, $output)
	{
		$arguments = array($lensDirectory, $fixture, $input, $output);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$executable} --expected={$encoded}";
	}
}
