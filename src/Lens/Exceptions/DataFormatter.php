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

namespace _Lens\Lens\Exceptions;

use _Lens\Lens\Displayer;

class DataFormatter
{
	/**
	 * @param array $data
	 * @param integer $depth
	 * @return string
	 */
	public function formatExceptionData(array $data, $depth = 0)
	{
		$lines = [];

		$padding = str_repeat(' ', ++$depth * 3);

		$lines[] = "{$padding}Message: " . $this->getValue($data['message']);
		$lines[] = "{$padding}File: " . $this->getValue($data['file']);
		$lines[] = "{$padding}Line: " . $this->getValue($data['line']);
		$lines[] = "{$padding}Class: " . $this->getValue($data['class']);

		if ($data['code'] !== 0) {
			$lines[] = "{$padding}Code: " . $this->getValue($data['code']);
		}

		foreach ($data['properties'] as $class => $properties) {
			foreach ($properties as $name => $value) {
				$nameText = ucfirst($name);

				if (($class === 'ErrorException') && ($name === 'severity')) {
					$valueText = self::getSeverityConstantName($value);
				} else {
					$valueText = $this->getValue($value);
				}

				$lines[] = "{$padding}{$nameText}: {$valueText}";
			}
		}

		if ($data['exception'] !== null) {
			$lines[] = "{$padding}Previous exception:\n" . $this->formatExceptionData($data['exception'], $depth);
		}

		return implode("\n", $lines);
	}

	private static function getSeverityConstantName($value)
	{
		switch ($value) {
			case E_ERROR:
				return 'E_ERROR';

			case E_WARNING:
				return 'E_WARNING';

			case E_PARSE:
				return 'E_PARSE';

			case E_NOTICE:
				return 'E_NOTICE';

			case E_CORE_ERROR:
				return 'E_CORE_ERROR';

			case E_CORE_WARNING:
				return 'E_CORE_WARNING';

			case E_COMPILE_ERROR:
				return 'E_COMPILE_ERROR';

			case E_COMPILE_WARNING:
				return 'E_COMPILE_WARNING';

			case E_USER_ERROR:
				return 'E_USER_ERROR';

			case E_USER_WARNING:
				return 'E_USER_WARNING';

			case E_USER_NOTICE:
				return 'E_USER_NOTICE';

			case E_STRICT:
				return 'E_STRICT';

			case E_RECOVERABLE_ERROR:
				return 'E_RECOVERABLE_ERROR';

			case E_DEPRECATED:
				return 'E_DEPRECATED';

			case E_USER_DEPRECATED:
				return 'E_USER_DEPRECATED';

			case E_ALL:
				return 'E_ALL';

			default:
				return $value;
		}
	}

	private function getValue($value)
	{
		$displayer = new Displayer();
		return $displayer->display($value);
	}
}
