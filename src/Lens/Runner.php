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

use Lens\Evaluator\Evaluator;

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

	/** @var Settings */
	private $settings;

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

	/** @var Logger */
	private $logger;

	public function __construct(Settings $settings, Filesystem $filesystem, Browser $browser, Evaluator $evaluator, Console $console, Web $web, Logger $logger)
	{
		$displayer = new Displayer();

		$this->settings = $settings;
		$this->filesystem = $filesystem;
		$this->browser = $browser;
		$this->evaluator = $evaluator;
		$this->console = $console;
		$this->web = $web;
		$this->displayer = $displayer;
		$this->logger = $logger;
	}

	public function run(array $paths)
	{
		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));

		$paths = array_map(array($this, 'getAbsoluteTestsPath'), $paths);

		if (!$this->findTests($paths, $tests)) {
			throw Exception::unknownTestsDirectory();
		}

		$lensDirectory = dirname($tests);
		$testsDirectory = "{$lensDirectory}/tests";
		$coverageDirectory = "{$lensDirectory}/coverage";

		$this->settings->setPath("{$lensDirectory}/" . self::$settingsFileName);
		$settings = $this->settings->read();

		$this->findSrc($settings, $lensDirectory, $srcDirectory);
		$this->findAutoloader($settings, $lensDirectory, $srcDirectory, $autoloaderPath);

		if (count($paths) === 0) {
			$paths[] = $testsDirectory;
		}

		$suites = $this->browser->browse($testsDirectory, $paths);
		list($suites, $code, $coverage) = $this->evaluator->run($lensDirectory, $srcDirectory, $autoloaderPath, $suites);

		echo $this->console->summarize($suites);

		if (isset($code, $coverage)) {
			$this->web->coverage($srcDirectory, $coverageDirectory, $code, $coverage);
		}

		restore_exception_handler();
		restore_error_handler();
	}

	public function errorHandler($level, $message, $file, $line)
	{
		throw Exception::error($level, trim($message), $file, $line);
	}

	/**
	 * @param \Throwable|\Exception $exception
	 */
	public function exceptionHandler($exception)
	{
		try {
			throw $exception;
		} catch (Exception $throwable) {
		} catch (\Throwable $throwable) {
			$exception = Exception::exception($throwable);
		} catch (\Exception $throwable) {
			$exception = Exception::exception($throwable);
		}

		$severity = $exception->getSeverity();
		$code = $exception->getCode();
		$message = $exception->getMessage();
		$help = $exception->getHelp();
		$data = $exception->getData();

		$output = $this->getStderrText($severity, $code, $message, $help, $data);
		file_put_contents("php://stderr", "{$output}\n");

		$output = $this->getSyslogText($severity, $code, $message, $data);
		$this->logger->log($severity, $output);

		exit($code);
	}

	private function getStderrText($severity, $code, $message, $help, $data)
	{
		$output = self::getSeverityText($severity) . " {$code}: {$message}";

		if (0 < count($help)) {
			$output .= "\n\nTROUBLESHOOTING\n\n" . $this->getHelpText($help);
		}

		if (0 < count($data)) {
			$output .= "\n\nINFORMATION\n\n" . $this->getDataText($data);
		}

		return $output;
	}

	private function getSyslogText($severity, $code, $message, $data)
	{
		$output = self::getSeverityText($severity) . " {$code}: {$message}";

		if (0 < count($data)) {
			$output .= ' ' . json_encode($data);
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

	private function findFile($basePath, $targetNames, &$output)
	{
		foreach ($targetNames as $targetName) {
			$path = "{$basePath}/{$targetName}";

			if ($this->filesystem->isFile($path)) {
				$output = $path;
				return true;
			}
		}

		return false;
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

	private function findSrc(array $settings, $lens, &$srcDirectory)
	{
		// TODO: refactor this:
		return $this->findSrcFromSettings($lens, $settings['src'], $srcDirectory) ||
			$this->findSrcFromLens($lens, $srcDirectory);
	}

	private function findSrcFromSettings($lens, &$relativePath, &$output)
	{
		if ($relativePath === null) {
			return false;
		}

		$absolutePath = $this->filesystem->getAbsolutePath("{$lens}/{$relativePath}");

		if ($absolutePath === null) {
			throw Exception::invalidSrcDirectory($relativePath);
		}

		$output = $absolutePath;
		return true;
	}

	private function findSrcFromLens($lens, &$output)
	{
		return $this->findChild($lens, self::$srcDirectoryName, $output) ||
			$this->findChild(dirname($lens), self::$srcDirectoryName, $output);
	}

	private function findAutoloader(array $settings, $lensDirectory, $srcDirectory, &$autoloaderPath)
	{
		return $this->findAutoloaderFromSettings($settings['autoloader'], $autoloaderPath) ||
			$this->findAutoloaderFromLens($lensDirectory, $autoloaderPath) ||
			$this->findAutoloaderFromSrc($srcDirectory, $autoloaderPath);
	}

	private function findAutoloaderFromSettings(&$autoloaderPathSettings, &$autoloaderPath)
	{
		if ($autoloaderPathSettings === null) {
			return false;
		}

		$autoloaderPath = $autoloaderPathSettings;
		return true;
	}

	private function findAutoloaderFromLens($lensDirectory, &$autoloaderPath)
	{
		$names = array('autoload.php', 'bootstrap.php', 'autoloader.php');

		return $this->findFile($lensDirectory, $names, $autoloaderPath);
	}

	private function findAutoloaderFromSrc($srcDirectory, &$autoloaderPath)
	{
		if ($srcDirectory === null) {
			return false;
		}

		$projectDirectory = dirname($srcDirectory);
		$path = "{$projectDirectory}/vendor/autoload.php";

		if (!$this->filesystem->isFile($path)) {
			return false;
		}

		$autoloaderPath = $path;
		return true;
	}
}
