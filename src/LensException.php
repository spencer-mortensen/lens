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

use ErrorException;
use Exception;
use Lens\Commands\Version;
use Throwable;

class LensException extends Exception
{
	/** @var string */
	private static $lensExecutable = 'lens';

	/** @var string */
	private static $lensCommandUrl = 'http://lens.guide/command/';
	private static $lensTestsFileUrl = 'http://lens.guide/tests-file/';
	private static $lensGuideUrl = 'http://lens.guide/#guide';
	private static $lensReportsUrl = 'http://lens.guide/command/';
	private static $lensIssuesUrl = 'https://github.com/Spencer-Mortensen/lens/issues';
	private static $lensInstallationUrl = 'http://lens.guide/installation/';
	private static $iniSyntaxUrl = 'https://en.wikipedia.org/wiki/INI_file';

	const CODE_FAILURES = 1;
	const CODE_USAGE = 2;
	const CODE_UNKNOWN_TESTS_DIRECTORY = 3;
	const CODE_INVALID_SETTINGS_FILE = 4;
	const CODE_INVALID_SRC_DIRECTORY = 5;
	const CODE_INVALID_AUTOLOADER_PATH = 6;
	const CODE_INVALID_TESTS_PATH = 7;
	const CODE_INVALID_TESTS_FILE_SYNTAX = 8;
	const CODE_INVALID_REPORT = 9;
	const CODE_PROCESSOR = 10;
	const CODE_INTERNAL = 255;

	const SEVERITY_NOTICE = 1; // Surprising, but might be normal, and no intervention is necessary (e.g. a configuration file is missing)
	const SEVERITY_WARNING = 2; // Definitely abnormal, but we can recover without human intervention (e.g. a configuration file is corrupt, and we can replace it with a clean one)
	const SEVERITY_ERROR = 3; // Definitely abnormal, and human intervention is required (e.g. a programming error)

	/** @var integer */
	private $severity;

	/** @var array|null */
	private $help;

	/** @var array|null */
	private $data;

	/**
	 * @param integer $code
	 * @param integer $severity
	 * @param string $message
	 * @param array|null $help
	 * @param array|null $data
	 * @param Throwable|Exception|null $previous
	 */
	public function __construct($code, $severity, $message, array $help = null, array $data = null, $previous = null)
	{
		parent::__construct($message, $code, $previous);

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

	public function getData()
	{
		return $this->data;
	}

	public static function usage()
	{
		$code = self::CODE_USAGE;

		$severity = self::SEVERITY_ERROR;

		$message = "Unknown Lens command.";

		$help = array(
			"Here is a list of the Lens commands that you can use:\n" . self::$lensCommandUrl
		);

		return new self($code, $severity, $message, $help);
	}

	/**
	 * @param Throwable|Exception $exception
	 * @return LensException
	 */
	public static function exception($exception)
	{
		$code = self::CODE_INTERNAL;

		$severity = self::SEVERITY_ERROR;

		$message = "Lens encountered an unpleasant error.";

		$help = array(
			"Check the issues page to see if there is a solution, or help others by filing a bug report:\n" . self::$lensIssuesUrl
		);

		$data = null;

		return new self($code, $severity, $message, $help, $data, $exception);
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

		return new self($code, $severity, $message, $help);
	}

	public static function invalidSettingsFile($path, ErrorException $exception)
	{
		$code = self::CODE_INVALID_SETTINGS_FILE;

		$severity = self::SEVERITY_WARNING;

		$message = "The settings file isn't a valid INI file.";

		$help = array(
			"Here is an article about the INI file format:\n" . self::$iniSyntaxUrl
		);

		// TODO: convert this absolute path into a relative path (based on the current working directory)
		$error = trim($exception->getMessage());
		$data = array(
			'file' => $path,
			'error' => $error
		);

		return new self($code, $severity, $message, $help, $data, $exception);
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

	public static function invalidTestsFileSyntax($path, $contents, $position, $expectation)
	{
		$code = self::CODE_INVALID_TESTS_FILE_SYNTAX;

		$severity = self::SEVERITY_ERROR;

		$message = self::getTestsFileInvalidSyntaxMessage($path, $contents, $position, $expectation);

		// TODO: add $data array with "expected" and "actual"

		$help = array(
			"Here is an article about the syntax of a tests file:\n" . self::$lensTestsFileUrl
		);

		return new self($code, $severity, $message, $help);
	}

	private static function getTestsFileInvalidSyntaxMessage($path, $contents, $position, $expectation)
	{
		$displayer = new Displayer();

		$pathText = $displayer->display($path);

		$message = "Syntax error in {$pathText}: ";

		list($line, $character) = self::getCoordinates($contents, $position);

		$tail = self::getTail($contents, $position);
		$tailText = $displayer->display($tail);

		if ($expectation === null) {
			$message .= "unexpected text ({$tailText}) at the end of the file.";
		} else {
			$expectationText = self::getExpectationText($expectation);
			$message .= "expected {$expectationText}";

			if (0 < strlen($tail)) {
				$message .= ", but read {$tailText}";
			}

			$message .= " at line {$line} character {$character}.";
		}

		return $message;
	}

	private static function getCoordinates($input, $position)
	{
		$lines = explode("\n", substr($input, 0, $position));
		$x = count($lines);

		$lastLine = array_pop($lines);
		$y = strlen($lastLine) + 1;

		return array($x, $y);
	}

	public static function getTail($input, $position)
	{
		$tail = substr($input, $position);
		$end = strpos($tail, "\n");

		if ($end === 0) {
			$end = 1;
		} elseif (!is_integer($end)) {
			$end = 96;
		}

		return substr($tail, 0, $end);
	}

	private static function getExpectationText($expectation)
	{
		switch ($expectation) {
			case 'phpTagLine':
				return "an opening PHP tag (\"<?php\\n\")";

			case 'codeUnit':
				return "PHP statements";

			case 'subjectLabel':
				return "a test label (\"// Test\\n\")";

			case 'outputLabel':
				return "an output label (\"// Output\\n\")";

			default:
				throw new ErrorException("Undefined expectation ({$expectation})", null, E_USER_ERROR, __FILE__, __LINE__);
		}
	}

	public static function invalidReport($reportType)
	{
		$code = self::CODE_INVALID_REPORT;

		$severity = self::SEVERITY_ERROR;

		$displayer = new Displayer();
		$reportText = $displayer->display($reportType);
		$message = "There is no report called {$reportText}!";

		$version = Version::VERSION;

		$help = array(
			"Make sure that the report name is spelled correctly. Here is a list of the supported reports:\n" . self::$lensReportsUrl,
			"Are you using the current version of Lens? (Your version is \"lens {$version}.\") If you need it, you can get the current version here:\n" . self::$lensInstallationUrl
		);

		return new self($code, $severity, $message, $help);
	}
}
