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

namespace Lens_0_0_56\SpencerMortensen\ParallelProcessor;

use Exception;

class ProcessorException extends Exception
{
	const CODE_READ_ERROR = 1;
	const CODE_WRITE_ERROR = 2;
	const CODE_TIMEOUT = 3;
	const CODE_MESSAGE = 4;
	const CODE_OPEN_PROCESS_ERROR = 4;
	const CODE_SOCKET_PAIR_ERROR = 5;
	const CODE_FORK_ERROR = 6;

	/** @var null|array */
	private $data;

	public function __construct($code, $message = null, array $data = null)
	{
		parent::__construct($message, $code);

		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public static function readError($stream, $contents)
	{
		return self::ioError(self::CODE_READ_ERROR, $stream, $contents, 'read from', 'input');
	}

	public static function writeError($stream, $contents)
	{
		return self::ioError(self::CODE_WRITE_ERROR, $stream, $contents, 'write to', 'output');
	}

	private static function ioError($code, $stream, $contents, $verb, $adjective)
	{
		$data = [
			'message' => $contents
		];

		$type = gettype($stream);

		switch ($type) {
			case 'unknown type':
				$message = "Invalid {$adjective} stream: the stream appears to be closed.";
				break;

			case 'resource':
				$message = "Unable to {$verb} the {$adjective} stream";
				break;

			default:
				$streamText = self::getTypeText($stream);
				$message = "Invalid {$adjective} stream: expected a stream, but received {$streamText} instead.";
				$data['stream'] = $stream;
		}

		return new self($code, $message, $data);
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

	public static function invalidMessage($message)
	{
		// TODO: show the corrupted message to the user
		$messageText = var_export($message, true);

		return new self(self::CODE_MESSAGE, "A process responded with an invalid message ({$messageText}).");
	}

	public static function timeout()
	{
		return new self(self::CODE_TIMEOUT, 'No jobs completed within the timeout period');
	}

	public static function openProcessError()
	{
		return new self(self::CODE_OPEN_PROCESS_ERROR, 'Unable to start a new process');
	}

	public static function socketPairError()
	{
		return new self(self::CODE_SOCKET_PAIR_ERROR, 'Unable to create a stream socket pair');
	}

	public static function forkError()
	{
		return new self(self::CODE_FORK_ERROR, 'Unable to fork the current process');
	}
}
