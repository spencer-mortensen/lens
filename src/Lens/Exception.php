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

namespace Lens;

class Exception extends \Exception
{
	/** @var string */
	private static $lensExecutable = 'lens';

	/** @var string */
	private static $lensGuideUrl = 'http://lens.guide/#guide';

	/** @var string */
	private static $lensIssuesUrl = 'https://github.com/Spencer-Mortensen/lens/issues';

	const CODE_INTERNAL = 0;
	const CODE_UNKNOWN_TESTS_DIRECTORY = 1;
	const CODE_INVALID_SETTINGS_FILE = 2;
	const CODE_INVALID_SRC_DIRECTORY = 3;
	const CODE_INVALID_AUTOLOADER_PATH = 4;
	const CODE_INVALID_TESTS_PATH = 5;
	const CODE_INVALID_TESTS_FILE = 6;

	const SEVERITY_NOTICE = 1; // Surprising, but might be normal, and no intervention is necessary (e.g. a configuration file is missing)
	const SEVERITY_WARNING = 2; // Definitely abnormal, but we can recover without human intervention (e.g. a configuration file is corrupt, and we can replace it with a clean one)
	const SEVERITY_ERROR = 3; // Definitely abnormal, and human intervention is required (e.g. a programming error)

	/** @var integer */
	private $severity;

	/** @var null|array */
	private $help;

	/** @var null|array */
	private $data;

	/**
	 * @param integer $code
	 * @param integer $severity
	 * @param string $message
	 * @param null|array $help
	 * @param null|array $data
	 */
	public function __construct($code, $severity, $message, array $help = null, array $data = null)
	{
		parent::__construct($message, $code);

		$this->severity = $severity;
		$this->help = $help;
		$this->data = $data;
	}

	/**
	 * @return integer
	 */
	public function getSeverity()
	{
		return $this->severity;
	}

	/**
	 * @return null|array
	 */
	public function getHelp()
	{
		return $this->help;
	}

	/**
	 * @return null|array
	 */
	public function getData()
	{
		return $this->data;
	}

	public static function error($errorLevel, $errorMessage, $file, $line)
	{
		$code = self::CODE_INTERNAL;

		$severity = self::getSeverityFromErrorLevel($errorLevel);

		$message = "Lens encountered an unexpected error.";

		$help = array(
			"Check the issues page to see if there is a solution, or help others by filing a bug report:\n" . self::$lensIssuesUrl
		);

		$data = array(
			'code' => $code,
			'message' => $errorMessage,
			'file' => $file,
			'line' => $line,
			'level' => $errorLevel
		);

		return new self($code, $severity, $message, $help, $data);
	}

	/**
	 * @param \Throwable $throwable
	 * @return Exception
	 */
	public static function exception($throwable)
	{
		$code = self::CODE_INTERNAL;

		$severity = self::SEVERITY_ERROR;

		$message = "Lens encountered an object that was thrown but never caught.";

		$help = array(
			"Check the issues page to see if there is a solution, or help others by filing a bug report:\n" . self::$lensIssuesUrl
		);

		$archivist = new Archivist\Archivist();

		$errorMessage = $throwable->getMessage();
		$file = $throwable->getFile();
		$line = $throwable->getLine();

		$data = array(
			'code' => $code,
			'message' => $errorMessage,
			'file' => $file,
			'line' => $line,
			'object' => $archivist->archive($throwable),
		);

		return new self($code, $severity, $message, $help, $data);
	}

	public static function unknownTestsDirectory()
	{
		$code = self::CODE_UNKNOWN_TESTS_DIRECTORY;

		$severity = self::SEVERITY_ERROR;

		$message = 'Unable to find the tests directory.';

		$help = array(
			"Do you have a tests directory? If not, you should check out this short guide to get started:\n" . self::$lensGuideUrl,
			"Is your tests directory called \"tests\"? You need to use that name exactly, without any spelling or capitalization differences.",
			"Is your tests directory directly under your project directory, or in a Lens directory that is directly under your project directory? Lens will find your tests directory only if it's pretty near the surface.",
			"Are you working outside your project directory right now? You can run your tests from anywhere by explicitly providing the path to your tests. Here's an example:\n" . self::$lensExecutable . " ~/MyProject/tests"
		);

		$data = null;

		return new self($code, $severity, $message, $help, $data);
	}

	public static function invalidSettingsFile($path, $errorMessage = null)
	{
		$code = self::CODE_INVALID_SETTINGS_FILE;

		$severity = self::SEVERITY_WARNING;

		$message = "The settings file isn't a valid INI file.";

		$help = array(
			"Here's an overview of the INI file format:\n" .
			"https://en.wikipedia.org/wiki/INI_file"
		);

		$data = array(
			'file' => $path
		);

		if (isset($errorMessage)) {
			$data['error'] = $errorMessage;
		}

		return new self($code, $severity, $message, $help, $data);
	}

	public static function invalidSrcDirectory($path)
	{
		$code = self::CODE_INVALID_SRC_DIRECTORY;

		$severity = self::SEVERITY_NOTICE;

		// TODO: this should check for a directory (not a path)
		// TODO: this should mention the configuration file
		$message = self::getInvalidPathMessage($path, 'a src directory path');

		$help = null;

		$data = array(
			'path' => $path
		);

		return new self($code, $severity, $message, $help, $data);
	}

	public static function invalidTestsPath($path)
	{
		$code = self::CODE_INVALID_TESTS_PATH;

		$severity = self::SEVERITY_ERROR;

		$message = self::getInvalidPathMessage($path, 'a tests file or directory');

		$help = null;

		$data = array(
			'path' => $path
		);

		return new self($code, $severity, $message, $help, $data);
	}

	public static function invalidAutoloaderPath($path)
	{
		$code = self::CODE_INVALID_AUTOLOADER_PATH;

		$severity = self::SEVERITY_ERROR;

		$message = self::getInvalidPathMessage($path, 'an autoloader file');

		$help = null;

		$data = array(
			'path' => $path
		);

		return new self($code, $severity, $message, $help, $data);
	}

	public static function invalidTestsFile($path)
	{
		$code = self::CODE_INVALID_TESTS_FILE;

		$severity = self::SEVERITY_ERROR;

		// TODO: display all quotations in double quotes:
		$pathValue = var_export($path, true);
		// TODO: improve this error message:
		$message = "The test file {$pathValue} has invalid syntax.";

		$help = null;

		$data = array(
			'file' => $path
		);

		return new self($code, $severity, $message, $help, $data);
	}

	private static function getSeverityFromErrorLevel($level)
	{
		switch ($level) {
			case E_STRICT:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			case E_NOTICE:
			case E_USER_NOTICE:
				return self::SEVERITY_NOTICE;

			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
			case E_RECOVERABLE_ERROR:
				return self::SEVERITY_WARNING;

			default:
				return self::SEVERITY_ERROR;
		}
	}

	private static function getInvalidPathMessage($path, $description)
	{
		// TODO: display all quotations in double quotes:
		$displayer = new Displayer();

		if (!is_string($path) || (strlen($path) === 0)) {
			$testsDirectoryValue = self::getValueDescription($path);
			return "Expected a path to {$description}, but received {$testsDirectoryValue} instead";
		}

		if (!file_exists($path)) {
			$testsDirectoryValue = $displayer->display($path);
			return "Expected a path to {$description}, but there doesn't seem to be anything at {$testsDirectoryValue}";
		}

		if (!is_dir($path) && !is_file($path)) {
			$testsDirectoryValue = $displayer->display($path);
			return "Expected a path to {$description}, but {$testsDirectoryValue} is not a file or a directory";
		}

		if (!is_readable($path)) {
			$testsDirectoryValue = $displayer->display($path);
			return "Expected a path to {$description}, but {$testsDirectoryValue} is not readable";
		}

		return "Expected a valid path to {$description}";
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
