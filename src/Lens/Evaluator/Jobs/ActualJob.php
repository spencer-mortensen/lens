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

use Lens\Evaluator\Actual;
use SpencerMortensen\ParallelProcessor\Shell\ShellJob;

class ActualJob implements ShellJob
{
	/** @var string */
	private $executable;

	/** @var string */
	private $lensDirectory;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $autoloaderPath;

	/** @var string */
	private $fixture;

	/** @var string */
	private $input;

	/** @var string */
	private $output;

	/** @var string */
	private $subject;

	/** @var null|array */
	private $results;

	/** @var null|array */
	private $coverage;

	public function __construct($executable, $lensDirectory, $srcDirectory, $autoloaderPath, $fixture, $input, $output, $subject, &$results, &$coverage)
	{
		$this->executable = $executable;
		$this->lensDirectory = $lensDirectory;
		$this->srcDirectory = $srcDirectory;
		$this->autoloaderPath = $autoloaderPath;
		$this->fixture = $fixture;
		$this->input = $input;
		$this->output = $output;
		$this->subject = $subject;

		$this->results = &$results;
		$this->coverage = &$coverage;
	}

	public function getCommand()
	{
		$arguments = array($this->lensDirectory, $this->srcDirectory, $this->autoloaderPath, $this->fixture, $this->input, $this->output, $this->subject);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --actual={$encoded}";
	}

	public function run($send)
	{
		$actual = new Actual($this->executable, $this->lensDirectory, $this->srcDirectory, $this->autoloaderPath);

		$onShutdown = function () use ($actual, $send) {
			$state = $actual->getState();
			$coverage = $actual->getCoverage();

			$message = serialize(array($state, $coverage));

			call_user_func($send, $message);
		};

		$actual->run($this->fixture, $this->input, $this->output, $this->subject, $onShutdown);
	}

	public function receive($message)
	{
		list($this->results, $this->coverage) = unserialize($message);
	}
}
