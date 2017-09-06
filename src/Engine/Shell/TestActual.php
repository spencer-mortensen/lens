<?php

namespace Lens\Engine\Shell;

use Lens\Engine\Agent;
use Lens\Engine\Code;
use Lens\Engine\CoverageExtractor;
use Lens\Engine\Shell\Jobs\ExpectedJob;
use Lens\Engine\Test;

class TestActual
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensDirectory;

	/** @var string */
	private $srcDirectory;

	/** @var Test */
	private $test;

	/** @var CoverageExtractor */
	private $coverageExtractor;

	/** @var null */
	private $script;

	/** @var array */
	private $state;

	/** @var null|array */
	private $coverage;

	public function __construct($executable, $lensDirectory, $srcDirectory)
	{
		$this->executable = $executable;
		$this->lensDirectory = $lensDirectory;
		$this->srcDirectory = $srcDirectory;
	}

	public function run($fixture, $input, $output, $subject)
	{
		$this->state = array(
			'fixture' => null,
			'expected' => null,
			'actual' => null
		);

		$this->getExpectedResults($fixture, $input, $output);

		if ($this->script === null) {
			$this->sendResults();
		}

		$code = new Code();
		$code->prepare($this->lensDirectory);

		list($prePhp, $postPhp) = $code->getActualPhp($fixture, $input, $subject);

		$this->test = new Test();

		$this->test->run($prePhp, array($this, 'onPreShutdown'));

		$this->onPreShutdown();

		$this->test->run($postPhp, array($this, 'onPostShutdown'));

		$this->onPostShutdown();
	}

	private function getExpectedResults($fixture, $input, $output)
	{
		$job = new ExpectedJob($this->executable, $this->lensDirectory, $fixture, $input, $output, $preState, $postState, $script);

		$processor = new Processor();
		$processor->start($job);
		$processor->halt();

		$this->state['fixture'] = self::getCleanPreState($preState);
		$this->state['expected'] = self::getCleanPostState($this->state['fixture'], $postState);
		$this->script = $script;
	}

	public function onPreShutdown()
	{
		$this->state['fixture'] = self::getCleanPreState($this->test->getState());

		if ($this->test->isTerminated()) {
			$this->sendResults();
		}

		Agent::startPlaying($this->script);
		$this->coverageExtractor = new CoverageExtractor($this->srcDirectory);
		$this->coverageExtractor->start();
	}

	public function onPostShutdown()
	{
		$this->state['actual'] = self::getCleanPostState($this->state['fixture'], $this->test->getState());
		$this->coverageExtractor->stop();
		$this->coverage = $this->coverageExtractor->getCoverage();

		$this->sendResults();
	}

	private function sendResults()
	{
		$results = array(
			'state' => $this->state,
			'coverage' => $this->coverage
		);

		echo serialize($results);
	}

	// TODO: this is duplicated in "TestExpected"
	private static function getCleanPreState(array $pre)
	{
		unset($pre['constants'][Code::LENS_CONSTANT_NAME]);

		return $pre;
	}

	// TODO: this is duplicated in "TestExpected"
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

	// TODO: this is duplicated in "TestExpected"
	private static function removeDuplicateKeys(array $a, array &$b)
	{
		foreach ($b as $key => $value) {
			if (array_key_exists($key, $a)) {
				unset($b[$key]);
			}
		}
	}
}
