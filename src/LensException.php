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

use Error;
use ErrorException;
use Exception;
use Lens\Commands\LensVersion;
use SpencerMortensen\Parser\ParserException;
use SpencerMortensen\Paths\Paths;

class LensException extends Exception
{
	/** @var string */
	private static $lensExecutable = 'lens';

	const CODE_FAILURES = 1;
	const CODE_USAGE = 2;
	const CODE_UNKNOWN_LENS_DIRECTORY = 3;
	const CODE_UNKNOWN_SRC_DIRECTORY = 4;
	const CODE_UNKNOWN_AUTOLOAD_FILE = 5;
	const CODE_MISSING_COMPOSER_DIRECTORY = 6;
	const CODE_INVALID_SETTINGS_FILE = 7;
	const CODE_INVALID_TESTS_PATH = 8;
	const CODE_INVALID_TESTS_FILE_SYNTAX = 9;
	const CODE_INVALID_REPORT = 10;
	const CODE_PROCESSOR = 11;
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
	 * @param Exception|Error|null $previous
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
			"Here is a list of the Lens commands that you can use:\n" . Url::LENS_COMMAND
		);

		return new self($code, $severity, $message, $help);
	}

	/**
	 * @param Exception|Error $exception
	 * @return LensException
	 */
	public static function exception($exception)
	{
		$code = self::CODE_INTERNAL;

		$severity = self::SEVERITY_ERROR;

		$message = "Lens encountered an error.";

		$help = array(
			"Check the issues page to see if there is a solution, or help others by filing a bug report:\n" . Url::LENS_ISSUES
		);

		$data = null;

		return new self($code, $severity, $message, $help, $data, $exception);
	}

	public static function unknownLensDirectory()
	{
		$code = self::CODE_UNKNOWN_LENS_DIRECTORY;

		$severity = self::SEVERITY_ERROR;

		$message = "Unable to find the \"lens\" directory.";

		$help = array(
			"Do you have a \"lens\" directory? If not, you should check out this short guide to get started:\n" . Url::LENS_GUIDE,
			"Is your lens directory called \"lens\"? You should use that name exactly, without any spelling or capitalization differences.",
			"Is your \"lens\" directory located right inside your project directory?",
			"Are you working outside your project directory right now? You can run your tests from anywhere by explicitly providing the path to your tests. Here's an example:\n" . self::$lensExecutable . " ~/MyProject/lens/tests"
		);

		return new self($code, $severity, $message, $help);
	}

	public static function unknownSrcDirectory()
	{
		$code = self::CODE_UNKNOWN_SRC_DIRECTORY;

		$severity = self::SEVERITY_ERROR;

		$message = "Unable to find the source-code directory.";

		$help = array(
			"Is your source-code directory called \"src\"? Is it located right inside your project directory? If not, then you should open your \"settings.ini\" file and customize the \"src\" path. You can read more about the \"settings.ini\" file here:\n" . Url::LENS_SETTINGS
		);

		return new self($code, $severity, $message, $help);
	}

	public static function unknownAutoloadFile()
	{
		$code = self::CODE_UNKNOWN_AUTOLOAD_FILE;

		$severity = self::SEVERITY_ERROR;

		$message = "Unable to find the autoload file.";

		$help = array(
			"If you do have an autoloader, then add the autoloader path to your \"settings.ini\" file:\n" . Url::LENS_SETTINGS,
			"If you don't have an autoloader, then create one now:\n" . Url::LENS_AUTOLOADER
		);

		return new self($code, $severity, $message, $help);
	}

	public static function missingComposerDirectory($absoluteProjectPath)
	{
		$code = self::CODE_MISSING_COMPOSER_DIRECTORY;

		$severity = self::SEVERITY_ERROR;

		$message = "Unable to find your Composer vendor directory.";

		$composerCommand = self::getComposerInstallCommand($absoluteProjectPath);

		$help = array(
			"Is Composer installed? You should install Composer now, if you haven't already:\n" . Url::COMPOSER_INSTALLATION,
			"Once Composer is installed, you should download the project dependencies like this:\n$composerCommand"
		);

		return new self($code, $severity, $message, $help);
	}

	private static function getComposerInstallCommand($absoluteProjectPath)
	{
		$relativeProjectPath = self::getRelativePath($absoluteProjectPath);

		if ($relativeProjectPath === '.') {
			return 'composer install';
		}

		$escapedRelativeProjectPath = escapeshellarg($relativeProjectPath);
		return "composer --working-dir={$escapedRelativeProjectPath} install";
	}

	public static function invalidSettingsFile($path, ErrorException $exception)
	{
		$code = self::CODE_INVALID_SETTINGS_FILE;

		$severity = self::SEVERITY_WARNING;

		$message = "The settings file isn't a valid INI file.";

		$help = array(
			"Here is an article about the INI file format:\n" . Url::INI_SYNTAX
		);

		// TODO: convert this absolute path into a relative path (based on the current working directory)
		$error = trim($exception->getMessage());
		$data = array(
			'file' => $path,
			'error' => $error
		);

		return new self($code, $severity, $message, $help, $data, $exception);
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

	public static function invalidTestsFileSyntax($path, $contents, ParserException $exception)
	{
		$code = self::CODE_INVALID_TESTS_FILE_SYNTAX;

		$severity = self::SEVERITY_ERROR;

		$message = self::getTestsFileInvalidSyntaxMessage($path, $contents, $exception);

		// TODO: add $data array with "expected" and "actual"

		$help = array(
			"Here is an article about the syntax of a tests file:\n" . Url::LENS_TESTS_FILE_SYNTAX
		);

		return new self($code, $severity, $message, $help);
	}

	private static function getTestsFileInvalidSyntaxMessage($absolutePath, $contents, ParserException $exception)
	{
		$position = $exception->getState();
		$expectation = $exception->getRule();

		$displayer = new Displayer();
		$relativePath = self::getRelativePath($absolutePath);
		$pathText = $displayer->display($relativePath);

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

	private static function getRelativePath($absolutePath)
	{
		$filesystem = new Filesystem();
		$paths = Paths::getPlatformPaths();

		$currentDirectory = $filesystem->getCurrentDirectory();
		return $paths->getRelativePath($currentDirectory, $absolutePath);
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

		$version = LensVersion::VERSION;

		$help = array(
			"Make sure that the report name is spelled correctly. Here is a list of the supported reports:\n" . Url::LENS_REPORTS,
			"Are you using the current version of Lens? (Your version is \"lens {$version}.\") If you need it, you can get the current version here:\n" . Url::LENS_INSTALLATION
		);

		return new self($code, $severity, $message, $help);
	}
}
