<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of TestPHP.
 *
 * TestPHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TestPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with TestPHP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@spencermortensen.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace TestPhp\Executable;

class Coverage
{
	public function run($filePath)
	{
		if (!function_exists('xdebug_start_code_coverage')) {
			echo 'null';
			exit(0);
		}

		ini_set('display_errors', 'Off');
		set_error_handler(function () {});
		register_shutdown_function(
			function () use ($filePath) {
				$coverage = Coverage::getCodeCoverage($filePath);
				Coverage::send($coverage);
			}
		);

		$code = "require '{$filePath}';";

		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

		eval($code);

		xdebug_stop_code_coverage(false);
	}

	public static function getCodeCoverage($filePath)
	{
		if (!function_exists('xdebug_get_code_coverage')) {
			return null;
		}

		$coverage = xdebug_get_code_coverage();
		$fileCoverage = &$coverage[$filePath];

		return $fileCoverage;
	}

	public static function send($output)
	{
		echo json_encode($output);
		exit(0);
	}
}
