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

namespace _Lens\Lens;

use Error;
use ErrorException;
use Exception;
use _Lens\Lens\Commands\VersionCommand;
use _Lens\Lens\Exceptions\ParsingException;
use _Lens\SpencerMortensen\Filesystem\Filesystem;
use _Lens\SpencerMortensen\Filesystem\Path;

class LensException extends Exception
{
	/** @var string */
	private static $lensExecutable = 'lens';

	const CODE_FAILURES = 1;
	const CODE_USAGE = 2;
	const CODE_UNKNOWN_LENS_DIRECTORY = 3;
	const CODE_UNKNOWN_SRC_DIRECTORY = 4;
	const CODE_INVALID_SETTINGS_FILE = 5;
	const CODE_INVALID_TESTS_FILE_SYNTAX = 7;
	const CODE_INVALID_REPORT = 8;
	const CODE_PROCESSOR = 9;
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

		$help = [
			"Here is a list of the Lens commands that you can use:\n" . Url::LENS_COMMAND
		];

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

		$help = [
			"Check the issues page to see if there is a solution, or help others by filing a bug report:\n" . Url::LENS_ISSUES
		];

		$data = null;

		return new self($code, $severity, $message, $help, $data, $exception);
	}

	public static function unknownLensDirectory()
	{
		$code = self::CODE_UNKNOWN_LENS_DIRECTORY;

		$severity = self::SEVERITY_ERROR;

		$message = "Unable to find the \"lens\" directory.";

		$help = [
			"Do you have a \"lens\" directory? If not, you should check out this short guide to get started:\n" . Url::LENS_GUIDE,
			"Are you working outside your project directory right now? You can run your tests from anywhere by explicitly providing the path to your tests. Here's an example:\n" . self::$lensExecutable . " ~/MyProject/lens/tests"
		];

		return new self($code, $severity, $message, $help);
	}

	public static function unknownSrcDirectory()
	{
		$code = self::CODE_UNKNOWN_SRC_DIRECTORY;

		$severity = self::SEVERITY_ERROR;

		$message = "Unable to find the source-code directory.";

		$help = [
			"Is your source-code directory called \"src\"? Is it located right inside your project directory? If not, then you should open your \"settings.yml\" file and customize the \"src\" path. You can read more about the \"settings.yml\" file here:\n" . Url::LENS_SETTINGS
		];

		return new self($code, $severity, $message, $help);
	}

	public static function invalidSettingsFile($path, ErrorException $exception)
	{
		$code = self::CODE_INVALID_SETTINGS_FILE;

		$severity = self::SEVERITY_WARNING;

		$message = "The settings file isn't a valid INI file.";

		$help = [
			"Here is an article about the INI file format:\n" . Url::INI_SYNTAX
		];

		// TODO: convert this absolute path into a relative path (based on the current working directory)
		$error = trim($exception->getMessage());
		$data = [
			'file' => (string)$path,
			'error' => $error
		];

		return new self($code, $severity, $message, $help, $data, $exception);
	}

	public static function invalidTestsFileSyntax(Path $path, ParsingException $exception)
	{
		$code = self::CODE_INVALID_TESTS_FILE_SYNTAX;

		$severity = self::SEVERITY_ERROR;

		$message = self::getTestsFileInvalidSyntaxMessage($path, $exception);

		// TODO: add $data array with "expected" and "actual"

		$help = [
			"Here is an article about the syntax of a tests file:\n" . Url::LENS_TESTS_FILE_SYNTAX
		];

		return new self($code, $severity, $message, $help);
	}

	private static function getTestsFileInvalidSyntaxMessage(Path $absolutePath, ParsingException $exception)
	{
		$displayer = new Displayer();

		$relativePath = self::getRelativePath($absolutePath);
		$pathText = $displayer->display((string)$relativePath);

		$expected = $exception->getExpected();
		$expectedText = self::getExpectedText($displayer, $expected);

		$actual = $exception->getActual();
		$actualText = self::getActualText($displayer, $actual);

		$coordinates = $exception->getCoordinates();
		$coordinatesText = self::getCoordinatesText($coordinates);

		return "Syntax error in {$pathText}: {$expectedText}{$actualText}{$coordinatesText}.";
	}

	private static function getRelativePath(Path $absolutePath)
	{
		$filesystem = new Filesystem();

		$currentDirectoryPath = $filesystem->getCurrentDirectoryPath();
		return $currentDirectoryPath->getRelativePath($absolutePath);
	}

	private static function getExpectedText(Displayer $displayer, $expected)
	{
		if ($expected === null) {
			$expectedValue = 'nothing';
		} else {
			$expectedValue = $displayer->display($expected);
		}

		return "expected {$expectedValue}";
	}

	private static function getActualText(Displayer $displayer, $actual)
	{
		if ($actual === null) {
			return '';
		}

		$actualValue = $displayer->display($actual);
		return " but read {$actualValue}";
	}

	public static function getCoordinatesText(array $coordinates)
	{
		list($x, $y) = $coordinates;

		$lineText = (string)($y + 1);
		$columnText = (string)($x + 1);

		if ($x === 0) {
			return " on line {$lineText}";
		}

		return " at line {$lineText} column {$columnText}";
	}

	public static function invalidReport($reportType)
	{
		$code = self::CODE_INVALID_REPORT;

		$severity = self::SEVERITY_ERROR;

		$displayer = new Displayer();
		$reportText = $displayer->display($reportType);
		$message = "There is no report called {$reportText}!";

		$version = VersionCommand::VERSION;

		$help = [
			"Make sure that the report name is spelled correctly. Here is a list of the supported reports:\n" . Url::LENS_REPORTS,
			"Are you using the current version of Lens? (Your version is \"lens {$version}.\") If you need it, you can get the current version here:\n" . Url::LENS_INSTALLATION
		];

		return new self($code, $severity, $message, $help);
	}
}
