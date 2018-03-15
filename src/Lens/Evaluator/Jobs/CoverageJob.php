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

namespace Lens_0_0_56\Lens\Evaluator\Jobs;

use Lens_0_0_56\Lens\Evaluator\Coverage;
use Lens_0_0_56\Lens\Filesystem;
use Lens_0_0_56\SpencerMortensen\Exceptions\Exceptions;
use Lens_0_0_56\SpencerMortensen\ParallelProcessor\ServerProcess;

class CoverageJob implements Job
{
	/** @var string */
	private $executable;

	/** @var string */
	private $srcDirectory;

	/** @var array */
	private $relativePaths;

	/** @var string */
	private $autoloadPath;

	/** @var null|ServerProcess */
	private $process;

	/** @var array */
	private $code;

	/** @var Coverage */
	private $coverage;

	public function __construct($executable, $srcDirectory, array $relativePaths, $autoloadPath, ServerProcess &$process = null, &$code, &$coverage)
	{
		$this->executable = $executable;
		$this->srcDirectory = $srcDirectory;
		$this->relativePaths = $relativePaths;
		$this->autoloadPath = $autoloadPath;
		$this->process = &$process;

		$this->code = &$code;
		$this->coverage = &$coverage;
	}

	public function getCommand()
	{
		$arguments = array($this->srcDirectory, $this->relativePaths, $this->autoloadPath);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --internal-coverage={$encoded}";
	}

	public function start()
	{
		$process = $this->process;
		// TODO: dependency injection:
		$filesystem = new Filesystem();
		$coverager = new Coverage($filesystem);

		$sendResult = function () use ($process, $coverager) {
			$code = $coverager->getCode();
			$coverage = $coverager->getCoverage();

			$process->sendResult(array($code, $coverage));
		};

		Exceptions::on($sendResult);

		$coverager->run($this->srcDirectory, $this->relativePaths, $this->autoloadPath);

		Exceptions::off();

		call_user_func($sendResult);
	}

	public function stop($message)
	{
		list($this->code, $this->coverage) = $message;
	}
}
