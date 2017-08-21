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

class Runner
{
	/** @var string */
	private static $testsDirectoryName = 'tests';

	/** @var string */
	private static $srcDirectoryName = 'src';

	/** @var string */
	private static $settingsFileName = 'settings.ini';

	/** @var integer */
	private static $maximumLineLength = 96;

	/** @var Filesystem */
	private $filesystem;

	/** @var Browser */
	private $browser;

	/** @var Evaluator */
	private $evaluator;

	/** @var Console */
	private $console;

	/** @var Web */
	private $web;

	/** @var Displayer */
	private $displayer;

	public function __construct(Filesystem $filesystem, Browser $browser, Evaluator $evaluator, Console $console, Web $web)
	{
		$displayer = new Displayer();

		$this->filesystem = $filesystem;
		$this->browser = $browser;
		$this->evaluator = $evaluator;
		$this->console = $console;
		$this->web = $web;
		$this->displayer = $displayer;
	}

	public function run($executable, array $paths)
	{
		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));

		$paths = array_map(array($this, 'getAbsoluteTestsPath'), $paths);

		if (!$this->findTests($paths, $tests)) {
			throw Exception::unknownTestsDirectory();
		}

		$testphpDirectory = dirname($tests);
		$testsDirectory = "{$testphpDirectory}/tests";
		$coverageDirectory = "{$testphpDirectory}/coverage";

		$settings = $this->getSettings($testphpDirectory);
		$this->findSrc($settings, $testphpDirectory, $srcDirectory);

		if (count($paths) === 0) {
			$paths[] = $testsDirectory;
		}

		$tests = $this->browser->browse($paths);
		$results = $this->evaluator->evaluate($tests, $testphpDirectory, $srcDirectory, $executable);

		echo $this->console->summarize($results['suites']);
		$this->web->coverage($srcDirectory, $coverageDirectory, $results['coverage']);

		restore_exception_handler();
		restore_error_handler();
	}

	public static function errorHandler($level, $message, $file, $line)
	{
		throw Exception::error($level, trim($message), $file, $line);
	}

	/**
	 * @param \Throwable $exception
	 */
	public function exceptionHandler($exception)
	{
		$code = $exception->getCode();

		try {
			throw $exception;
		} catch (Exception $throwable) {
			// Already an Exception object
		} catch (\Throwable $throwable) {
			$exception = Exception::exception($throwable);
		} catch (\Exception $throwable) {
			$exception = Exception::exception($throwable);
		}

		$stderr = $this->getStderrText($exception);

		file_put_contents('php://stderr', "{$stderr}\n\n");
		exit($code);
	}

	private function getStderrText(Exception $exception)
	{
		$code = $exception->getCode();
		$severity = $exception->getSeverity();
		$message = $exception->getMessage();
		$help = $exception->getHelp();
		$data = $exception->getData();

		$output = self::getSeverityText($severity) . " {$code}: {$message}";

		if (0 < count($help)) {
			$output .= "\n\nTROUBLESHOOTING\n\n" . $this->getHelpText($help);
		}

		if (0 < count($data)) {
			$output .= "\n\nINFORMATION\n\n" . $this->getDataText($data);
		}

		return $output;
	}

	private static function getSeverityText($severity)
	{
		switch ($severity) {
			case Exception::SEVERITY_NOTICE:
				return 'Note';

			case Exception::SEVERITY_WARNING:
				return 'Warning';

			default:
				return 'Error';
		}
	}

	private function getHelpText(array $help)
	{
		$output = array();

		foreach ($help as $paragraph) {
			$line = self::wrap($paragraph);
			$line = self::pad($line, '   ');
			$line = substr_replace($line, '*', 1, 1);

			$output[] = $line;
		}

		return implode("\n\n", $output);
	}

	private static function wrap($string)
	{
		return wordwrap($string, self::$maximumLineLength, "\n", true);
	}

	private static function pad($string, $prefix)
	{
		$pattern = self::getPattern('^(.+)$', 'm');
		$replacement = preg_quote($prefix) . '$1';

		return preg_replace($pattern, $replacement, $string);
	}

	private static function getPattern($expression, $flags = null)
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}

	private function getDataText(array $data)
	{
		$output = array();

		foreach ($data as $key => $value) {
			$keyText = ucfirst($key);
			$valueText = $this->displayer->display($value);

			$output[] = " * {$keyText}: {$valueText}";
		}

		return implode("\n", $output);
	}

	private function getAbsoluteTestsPath($relativePath)
	{
		$absolutePath = $this->filesystem->getAbsolutePath($relativePath);

		if ($absolutePath === null) {
			throw Exception::invalidTestsPath($relativePath);
		}

		return $absolutePath;
	}

	private function findTests(array $paths, &$output)
	{
		$current = $this->filesystem->getCurrentDirectory();

		return $this->findTestsFromPaths($paths, $output) ||
			$this->findTestsFromDirectory($current, $output);
	}

	private function findTestsFromPaths(array $paths, &$output)
	{
		return (0 < count($paths)) &&
			$this->findAncestor(current($paths), self::$testsDirectoryName, $output);
	}

	private function findAncestor($basePath, $targetName, &$output)
	{
		$trail = self::getTrail($basePath);

		for ($i = count($trail) - 1; -1 < $i; --$i) {
			if ($trail[$i] === $targetName) {
				$output = '/' . implode('/', array_slice($trail, 0, $i + 1));
				return true;
			}
		}

		return false;
	}

	private static function getTrail($path)
	{
		$path = trim($path, '/');

		if (strlen($path) === 0) {
			return array();
		}

		return explode('/', $path);
	}

	private function findTestsFromDirectory($directory, &$output)
	{
		return ($directory !== '/') && (
			$this->findChild($directory, self::$testsDirectoryName, $output) ||
			$this->findGrandchild($directory, self::$testsDirectoryName, $output) ||
			$this->findTestsFromDirectory(dirname($directory), $output)
		);
	}

	private function findChild($basePath, $targetName, &$output)
	{
		$path = "{$basePath}/{$targetName}";

		if (!$this->filesystem->isDirectory($path)) {
			return false;
		}

		$output = $path;
		return true;
	}

	private function findGrandchild($basePath, $targetName, &$output)
	{
		$paths = $this->filesystem->search("{$basePath}/*/{$targetName}");
		$paths = array_filter($paths, array($this->filesystem, 'isDirectory'));

		if (count($paths) !== 1) {
			return false;
		}

		$output = $paths[0];
		return true;
	}

	private function findSrc(array $settings, $testphp, &$srcDirectory)
	{
		// TODO: refactor this:
		return $this->findSrcFromSetting($testphp, $settings['src'], $srcDirectory) ||
			$this->findSrcFromTestphp($testphp, $srcDirectory);
	}

	private function findSrcFromSetting($testphp, &$relativePath, &$output)
	{
		if ($relativePath === null) {
			return false;
		}

		$absolutePath = $this->filesystem->getAbsolutePath("{$testphp}/{$relativePath}");

		if ($absolutePath === null) {
			throw Exception::invalidSrcDirectory($relativePath);
		}

		$output = $absolutePath;
		return true;
	}

	private function findSrcFromTestphp($testphp, &$output)
	{
		return $this->findChild($testphp, self::$srcDirectoryName, $output) ||
			$this->findChild(dirname($testphp), self::$srcDirectoryName, $output);
	}

	private function getSettings($testphp)
	{
		$path = $testphp . '/' . self::$settingsFileName;
		$contents = $this->filesystem->read($path);

		if ($contents === null) {
			return array();
		}

		try {
			$settings = parse_ini_string($contents, false);
		} catch (Exception $exception) {
			$data = $exception->getData();
			$errorMessage = $data['message'];

			throw Exception::invalidSettingsFile($path, $errorMessage);
		}

		if (!is_array($settings)) {
			throw Exception::invalidSettingsFile($path);
		}

		return $settings;
	}
}
