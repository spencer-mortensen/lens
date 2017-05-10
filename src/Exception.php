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

namespace TestPhp;

class Exception extends \Exception
{
	const INVALID_TESTS_DIRECTORY = 1;
	const INVALID_TEST_FILE = 2;

	/** @var mixed */
	private $data;

	/**
	 * @param int $code
	 * @param mixed $data
	 * @param string|null $message
	 */
	public function __construct($code, $data = null, $message = null)
	{
		parent::__construct($message, $code);

		$this->data = $data;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	public static function invalidTestsDirectory($testsDirectory)
	{
		$data = array(
			'directory' => $testsDirectory
		);

		if (!is_string($testsDirectory) || (strlen($testsDirectory) === 0)) {
			$testsDirectoryValue = self::getValueDescription($testsDirectory);
			$message = "Expected a path to a tests directory, but received {$testsDirectoryValue} instead";
		} elseif (!file_exists($testsDirectory)) {
			$testsDirectoryValue = json_encode($testsDirectory);
			$message = "Expected a path to a tests directory, but there is no directory at {$testsDirectoryValue}";
		} elseif (!is_dir($testsDirectory)) {
			$testsDirectoryValue = json_encode($testsDirectory);
			$message = "Expected a path to a tests directory, but {$testsDirectoryValue} is not a directory";
		} elseif (!is_readable($testsDirectory)) {
			$testsDirectoryValue = json_encode($testsDirectory);
			$message = "Expected a path to a tests directory, but {$testsDirectoryValue} is not readable";
		} else {
			$message = "Expected a valid path to a tests directory";
		}

		return new self(self::INVALID_TESTS_DIRECTORY, $data, $message);
	}

	public static function invalidTestFile($testFile)
	{
		$data = array(
			'file' => $testFile
		);

		// TODO: improve this error message:
		$message = 'The test file could not be read';

		return new self(self::INVALID_TEST_FILE, $data, $message);
	}

	private static function getValueDescription($value)
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
				if (strlen($value) === 0) {
					return 'an empty string';
				}

				$valueJson = json_encode($value);
				return "a string ({$valueJson})";

			case 'array':
				$valueJson = json_encode($value);
				return "an array ({$valueJson})";

			case 'object':
				return 'an object';

			case 'resource':
				return 'a resource';

			default:
				return 'an unknown value';
		}
	}
}
