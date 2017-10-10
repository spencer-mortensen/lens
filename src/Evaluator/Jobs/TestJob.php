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

use Lens\Evaluator\Test;
use SpencerMortensen\ParallelProcessor\Shell\ShellJob;

class TestJob implements ShellJob
{
	/** @var string */
	private $executable;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $autoloadPath;

	/** @var string */
	private $contextPhp;

	/** @var string */
	private $beforePhp;

	/** @var string */
	private $afterPhp;

	/** @var null|array */
	private $script;

	/** @var null|array */
	private $preState;

	/** @var null|array */
	private $postState;

	/** @var null|array */
	private $coverage;

	public function __construct($executable, $srcDirectory, $autoloadPath, $contextPhp, $beforePhp, $afterPhp, $script, array &$preState = null, array &$postState = null, array &$coverage = null)
	{
		$this->executable = $executable;
		$this->srcDirectory = $srcDirectory;
		$this->autoloadPath = $autoloadPath;
		$this->contextPhp = $contextPhp;
		$this->beforePhp = $beforePhp;
		$this->afterPhp = $afterPhp;
		$this->script = $script;
		$this->preState = &$preState;
		$this->postState = &$postState;
		$this->coverage = &$coverage;
	}

	public function getCommand()
	{
		$arguments = array($this->srcDirectory, $this->autoloadPath, $this->contextPhp, $this->beforePhp, $this->afterPhp, $this->script);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --test={$encoded}";
	}

	public function run($send)
	{
		$test = new Test($this->srcDirectory, $this->autoloadPath);

		$onShutdown = function () use ($test, $send) {
			$preState = $test->getPreState();
			$postState = $test->getPostState();
			$coverage = $test->getCoverage();

			$message = serialize(array($preState, $postState, $coverage));

			call_user_func($send, $message);
		};

		$test->run($this->contextPhp, $this->beforePhp, $this->afterPhp, $this->script, $onShutdown);
	}

	public function receive($message)
	{
		list($this->preState, $this->postState, $this->coverage) = unserialize($message);
	}
}
