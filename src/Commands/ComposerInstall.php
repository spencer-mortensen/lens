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

namespace Lens\Commands;

class ComposerInstall implements Command
{
	/** @var integer */
	const STDOUT = 1;

	/** @var integer */
	const STDERR = 2;

	/** @var string|null */
	private $workingDirectory;

	public function __construct($workingDirectory)
	{
		$this->workingDirectory = $workingDirectory;
	}

	public function run(&$stdout = null, &$stderr = null, &$exitCode = null)
	{
		$command = 'composer install';

		$descriptor = array(
			self::STDOUT => array('pipe', 'w'),
			self::STDERR => array('pipe', 'w')
		);

		$process = proc_open($command, $descriptor, $pipes, $this->workingDirectory);

		if (!is_resource($process)) {
			return null;
		}

		fclose($pipes[self::STDOUT]);
		fclose($pipes[self::STDERR]);

		return proc_close($process);
	}
}
