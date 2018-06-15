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

namespace Lens_0_0_56\Lens\Commands;

use Lens_0_0_56\SpencerMortensen\Filesystem\Paths\Path;

class ComposerInstall implements Command
{
	/** @var integer */
	const STDOUT = 1;

	/** @var integer */
	const STDERR = 2;

	/** @var Path */
	private $workingDirectory;

	public function __construct(Path $workingDirectory)
	{
		$this->workingDirectory = $workingDirectory;
	}

	public function run(&$stdout = null, &$stderr = null, &$exitCode = null)
	{
		$command = 'composer install';

		$descriptor = [
			self::STDOUT => ['pipe', 'w'],
			self::STDERR => ['pipe', 'w']
		];

		$workingDirectory = (string)$this->workingDirectory;
		$process = proc_open($command, $descriptor, $pipes, $workingDirectory);

		if (!is_resource($process)) {
			return null;
		}

		fclose($pipes[self::STDOUT]);
		fclose($pipes[self::STDERR]);

		return proc_close($process);
	}
}
