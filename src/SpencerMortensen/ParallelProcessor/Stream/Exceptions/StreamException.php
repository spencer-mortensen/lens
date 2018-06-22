<?php

/**
 * Copyright (C) 2017 Spencer Mortensen
 *
 * This file is part of parallel-processor.
 *
 * Parallel-processor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Parallel-processor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with parallel-processor. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen <spencer@lens.guide>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2017 Spencer Mortensen
 */

namespace Lens_0_0_57\SpencerMortensen\ParallelProcessor\Stream\Exceptions;

use Exception;

class StreamException extends Exception
{
	const CODE_OTHER = 0;
	const CODE_UNKNOWN = 1;

	/** @var array|null */
	private $data;

	public function __construct($stream)
	{
		list($code, $data, $message) = $this->getArguments($stream);

		parent::__construct($message, $code);

		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	private function getArguments($stream)
	{
		$type = gettype($stream);

		if ($type === 'unknown type') {
			return $this->getUnknown();
		}

		return $this->getOther($stream);
	}

	private function getUnknown()
	{
		$code = self::CODE_UNKNOWN;

		$data = null;

		$message = 'The stream seems to be closed.';

		return [$code, $data, $message];
	}

	private function getOther($value)
	{
		$code = self::CODE_OTHER;

		$data = [
			'stream' => $value
		];

		$valueText = self::getTypeText($value);
		$message = "Expected a stream, but received {$valueText} instead.";

		return [$code, $data, $message];
	}

	private static function getTypeText($value)
	{
		$type = gettype($value);

		switch ($type) {
			case 'NULL':
				return 'a null value';

			case 'boolean':
				$valueJson = json_encode($value);
				return "a boolean ({$valueJson})";

			case 'integer':
				$valueJson = json_encode($value);
				return "an integer ({$valueJson})";

			case 'double':
				$valueJson = json_encode($value);
				return "a float ({$valueJson})";

			case 'string':
				$valueJson = json_encode($value);
				return "a string value ({$valueJson})";

			case 'array':
				$valueJson = json_encode($value);
				return "an array ({$valueJson})";

			case 'object':
				return 'an object';

			case 'resource':
				return 'a resource';

			default:
				$typeJson = json_encode($type);
				return "a {$typeJson} value";
		}
	}
}
