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

namespace Lens\Evaluator;

use Lens\Evaluator\Jobs\ExpectedJob;

class Actual
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensDirectory;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $autoloaderPath;

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

	/** @var callable */
	private $onShutdown;

	public function __construct($executable, $lensDirectory, $srcDirectory, $autoloaderPath)
	{
		$this->executable = $executable;
		$this->lensDirectory = $lensDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->autoloaderPath = $autoloaderPath;
	}

	public function run($fixture, $input, $output, $subject, $onShutdown)
	{
		$this->onShutdown = $onShutdown;

		$this->state = array(
			'fixture' => null,
			'expected' => null,
			'actual' => null
		);

		$this->getExpectedResults($fixture, $input, $output);

		if ($this->script === null) {
			call_user_func($this->onShutdown);
		}

		$code = new Code();
		$code->prepare($this->lensDirectory, $this->autoloaderPath);

		list($prePhp, $postPhp) = $code->getActualPhp($fixture, $input, $subject);

		$this->test = new Test();

		$this->test->run($prePhp, array($this, 'onPreShutdown'));

		$this->onPreShutdown();

		$this->test->run($postPhp, array($this, 'onPostShutdown'));

		$this->onPostShutdown();
	}

	public function getState()
	{
		return $this->state;
	}

	public function getCoverage()
	{
		return $this->coverage;
	}

	private function getExpectedResults($fixture, $input, $output)
	{
		$job = new ExpectedJob($this->executable, $this->lensDirectory, $this->autoloaderPath, $fixture, $input, $output, $preState, $postState, $script);

		$processor = new Processor();
		$processor->start($job);
		$processor->finish();

		$this->state['fixture'] = self::getCleanPreState($preState);
		$this->state['expected'] = self::getCleanPostState($this->state['fixture'], $postState);
		$this->script = $script;
	}

	public function onPreShutdown()
	{
		$this->state['fixture'] = self::getCleanPreState($this->test->getState());

		if ($this->test->isTerminated()) {
			call_user_func($this->onShutdown);
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

		call_user_func($this->onShutdown);
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
