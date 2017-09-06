<?php

namespace Lens\Engine\Shell;

use Lens\Engine\Agent;
use Lens\Engine\Code;
use Lens\Engine\Test;

class TestExpected
{
	/** @var string */
	private $lensDirectory;

	/** @var Test */
	private $test;

	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var null */
	private $script;

	public function __construct($lensDirectory)
	{
		$this->lensDirectory = $lensDirectory;
	}

	public function run($fixture, $input, $output)
	{
		$code = new Code();
		$code->prepare($this->lensDirectory);

		list($prePhp, $postPhp) = $code->getExpectedPhp($fixture, $input, $output);

		$this->test = new Test();

		$this->test->run($prePhp, array($this, 'onPreShutdown'));

		$this->onPreShutdown();

		$this->test->run($postPhp, array($this, 'onPostShutdown'));

		$this->onPostShutdown();
	}

	public function onPreShutdown()
	{
		$this->preState = self::getCleanPreState($this->test->getState());

		if ($this->test->isTerminated()) {
			$this->sendResults();
		}

		Agent::startRecording();
	}

	public function onPostShutdown()
	{
		$this->postState = self::getCleanPostState($this->preState, $this->test->getState());

		$this->script = Agent::getScript();

		$this->sendResults();
	}

	private function sendResults()
	{
		$results = array($this->preState, $this->postState, $this->script);

		echo serialize($results);
	}

	private static function getCleanPreState(array $pre)
	{
		unset($pre['constants'][Code::LENS_CONSTANT_NAME]);

		return $pre;
	}

	private static function getCleanPostState(array $pre, array $post = null)
	{
		if ($post === null) {
			return null;
		}

		unset($post['constants'][Code::LENS_CONSTANT_NAME]);

		self::removeDuplicateKeys($pre['variables'], $post['variables']);
		self::removeDuplicateKeys($pre['globals'], $post['globals']);
		self::removeDuplicateKeys($pre['constants'], $post['constants']);

		return $post;
	}

	private static function removeDuplicateKeys(array $a, array &$b)
	{
		foreach ($b as $key => $value) {
			if (array_key_exists($key, $a)) {
				unset($b[$key]);
			}
		}
	}
}
