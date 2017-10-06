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

use Lens\Evaluator\Coverage;
use Lens\Filesystem;
use Lens\Logger;
use SpencerMortensen\ParallelProcessor\Shell\ShellJob;

class CoverageJob implements ShellJob
{
	/** @var string */
	private $executable;

	/** @var string */
	private $srcDirectory;

	/** @var array */
	private $relativePaths;

	/** @var string */
	private $autoloadPath;

	/** @var array */
	private $code;

	/** @var Coverage */
	private $coverage;

	public function __construct($executable, $srcDirectory, array $relativePaths, $autoloadPath, &$code, &$coverage)
	{
		$this->executable = $executable;
		$this->srcDirectory = $srcDirectory;
		$this->relativePaths = $relativePaths;
		$this->autoloadPath = $autoloadPath;

		$this->code = &$code;
		$this->coverage = &$coverage;
	}

	public function getCommand()
	{
		$arguments = array($this->srcDirectory, $this->relativePaths, $this->autoloadPath);
		$serialized = serialize($arguments);
		$compressed = gzdeflate($serialized, -1);
		$encoded = base64_encode($compressed);

		return "{$this->executable} --coverage={$encoded}";
	}

	public function run($send)
	{
		// TODO: dependency injection:
		$filesystem = new Filesystem();
		$logger = new Logger('lens');

		$coverager = new Coverage($filesystem, $logger);

		$onShutdown = function () use ($coverager, $send) {
			$code = $coverager->getCode();
			$coverage = $coverager->getCoverage();

			$message = serialize(array($code, $coverage));

			call_user_func($send, $message);
		};

		$coverager->run($this->srcDirectory, $this->relativePaths, $this->autoloadPath, $onShutdown);
	}

	public function receive($message)
	{
		list($this->code, $this->coverage) = unserialize($message);
	}
}
