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

use Lens\Command;
use Lens\Evaluator\Evaluator;
use Lens\Evaluator\Processor;
use Lens\Filesystem;
use SpencerMortensen\ParallelProcessor\Fork\ForkJob;

class EvaluatorJob implements ForkJob
{
	/** @var string */
	private $executable;

	/** @var Filesystem */
	private $filesystem;

	/** @var Processor */
	private $processor;

	/** @var string */
	private $srcDirectory;

	/** @var string */
	private $autoloadPath;

	/** @var array */
	private $suites;

	/** @var array */
	private $code;

	/** @var array */
	private $coverage;

	public function __construct($executable, Filesystem $filesystem, Processor $processor, $srcDirectory, $autoloadPath, array &$suites, array &$code = null, array &$coverage = null)
	{
		$this->executable = $executable;
		$this->filesystem = $filesystem;
		$this->processor = $processor;
		$this->srcDirectory = $srcDirectory;
		$this->autoloadPath = $autoloadPath;
		$this->suites = &$suites;
		$this->code = &$code;
		$this->coverage = &$coverage;
	}

	public function run($send)
	{
		Command::setIsInternalCommand(true);

		$evaluator = new Evaluator($this->executable, $this->filesystem, $this->processor);
		$results = $evaluator->run($this->srcDirectory, $this->autoloadPath, $this->suites);
		$message = serialize($results);

		call_user_func($send, $message);
	}

	public function receive($message)
	{
		list($this->suites, $this->code, $this->coverage) = unserialize($message);
	}
}
