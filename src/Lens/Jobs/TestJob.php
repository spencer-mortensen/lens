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

namespace Lens_0_0_56\Lens\Jobs;

use Lens_0_0_56\Lens\Tests\Test;
use Lens_0_0_56\SpencerMortensen\Exceptions\Exceptions;
use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\ServerProcess;

class TestJob implements Job
{
	/** @var string */
	private $executable;

	/** @var Path */
	private $lensCore;

	/** @var Path */
	private $cache;

	/** @var string */
	private $contextPhp;

	/** @var string */
	private $prePhp;

	/** @var string */
	private $postPhp;

	/** @var array */
	private $script;

	/** @var array */
	private $mockClasses;

	/** @var boolean */
	private $isActual;

	/** @var ServerProcess|null */
	private $process;

	/** @var array|null */
	private $preState;

	/** @var array|null */
	private $postState;

	/** @var array|null */
	private $coverage;

	public function __construct($executable, Path $lensCore, Path $cache, $contextPhp, $prePhp, $postPhp, array $script, array $mockClasses, $isActual, ServerProcess &$process = null, array &$preState = null, array &$postState = null, array &$coverage = null)
	{
		$this->executable = $executable;
		$this->lensCore = $lensCore;
		$this->cache = $cache;
		$this->contextPhp = $contextPhp;
		$this->prePhp = $prePhp;
		$this->postPhp = $postPhp;
		$this->script = $script;
		$this->mockClasses = $mockClasses;
		$this->isActual = $isActual;
		$this->process = &$process;
		$this->preState = &$preState;
		$this->postState = &$postState;
		$this->coverage = &$coverage;
	}

	public function getCommand()
	{
		$arguments = [(string)$this->lensCore, (string)$this->cache, $this->contextPhp, $this->prePhp, $this->postPhp, $this->script, $this->mockClasses, $this->isActual];
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --internal-test={$encoded}";
	}

	public function start()
	{
		$test = new Test($this->lensCore, $this->cache);
		$process = $this->process;

		$sendResult = function () use ($test, $process) {
			$result = [
				$test->getPreState(),
				$test->getPostState(),
				$test->getCoverage()
			];

			$process->sendResult($result);
		};

		Exceptions::on($sendResult);

		$test->run($this->contextPhp, $this->prePhp, $this->postPhp, $this->script, $this->mockClasses, $this->isActual);

		Exceptions::off();

		call_user_func($sendResult);
	}

	public function stop($message)
	{
		list($this->preState, $this->postState, $this->coverage) = $message;
	}
}
