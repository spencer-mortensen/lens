<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of testphp.
 *
 * Testphp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Testphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with testphp. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@testphp.org>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp;

class Shell
{
	private static $STDOUT = 1;
	private static $STDERR = 2;

	public function run($command, &$stdout, &$stderr, &$exitCode)
	{
		$descriptor = array(
			self::$STDOUT => array('pipe', 'w'),
			self::$STDERR => array('pipe', 'w')
		);

		$process = proc_open($command, $descriptor, $pipes);

		if (!is_resource($process)) {
			return false;
		}

		$stdout = self::pipe_close($pipes[self::$STDOUT]);
		$stderr = self::pipe_close($pipes[self::$STDERR]);
		$exitCode = proc_close($process);

		return true;
	}

	private static function pipe_close($pipe)
	{
		$output = stream_get_contents($pipe);
		fclose($pipe);

		return $output;
	}
}
