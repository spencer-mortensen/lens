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

class Expected
{
	/** @var Test */
	private $test;

	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var null */
	private $script;

	/** @var callable */
	private $onShutdown;

	public function __construct($lensDirectory, $autoloaderPath)
	{
		$this->lensDirectory = $lensDirectory;
		$this->autoloaderPath = $autoloaderPath;
	}

	public function run($fixture, $input, $output, $onShutdown)
	{
		$this->onShutdown = $onShutdown;

		$code = new Code();
		$code->prepare($this->lensDirectory, $this->autoloaderPath);

		list($prePhp, $postPhp) = $code->getExpectedPhp($fixture, $input, $output);

		$this->test = new Test();

		$this->test->run($prePhp, array($this, 'onPreShutdown'));

		$this->onPreShutdown();

		$this->test->run($postPhp, array($this, 'onPostShutdown'));

		$this->onPostShutdown();
	}

	public function getPreState()
	{
		return $this->preState;
	}

	public function getPostState()
	{
		return $this->postState;
	}

	public function getScript()
	{
		return $this->script;
	}

	public function onPreShutdown()
	{
		$this->preState = self::getCleanPreState($this->test->getState());

		if ($this->test->isTerminated()) {
			call_user_func($this->onShutdown);
		}

		Agent::startRecording();
	}

	public function onPostShutdown()
	{
		$this->postState = self::getCleanPostState($this->preState, $this->test->getState());

		$this->script = Agent::getScript();

		call_user_func($this->onShutdown);
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
