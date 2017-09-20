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

namespace Lens\Evaluator\Jobs;

use Lens\Evaluator\Expected;
use SpencerMortensen\ParallelProcessor\Shell\ShellJob;

class ExpectedJob implements ShellJob
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensDirectory;

	/** @var string */
	private $autoloaderPath;

	/** @var string */
	private $fixture;

	/** @var string */
	private $input;

	/** @var string */
	private $output;

	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var null|string */
	private $script;

	public function __construct($executable, $lensDirectory, $autoloaderPath, $fixture, $input, $output, &$preState, &$postState, &$script)
	{
		$this->executable = $executable;
		$this->lensDirectory = $lensDirectory;
		$this->autoloaderPath = $autoloaderPath;
		$this->fixture = $fixture;
		$this->input = $input;
		$this->output = $output;

		$this->preState = &$preState;
		$this->postState = &$postState;
		$this->script = &$script;
	}

	public function getCommand()
	{
		$arguments = array($this->lensDirectory, $this->autoloaderPath, $this->fixture, $this->input, $this->output);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --expected={$encoded}";
	}

	public function run($send)
	{
		$expected = new Expected($this->lensDirectory, $this->autoloaderPath);

		$onShutdown = function () use ($expected, $send) {
			$preState = $expected->getPreState();
			$postState = $expected->getPostState();
			$script = $expected->getScript();

			$message = serialize(array($preState, $postState, $script));

			call_user_func($send, $message);
		};

		$expected->run($this->fixture, $this->input, $this->output, $onShutdown);
	}

	public function receive($message)
	{
		list($this->preState, $this->postState, $this->script) = unserialize($message);
	}
}
