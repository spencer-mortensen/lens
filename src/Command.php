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

use Exception;
use Lens\Evaluator\Evaluator;
use Lens\Evaluator\Jobs\CoverageJob;
use Lens\Evaluator\Jobs\TestJob;
use Lens\Evaluator\Processor;
use Lens\Reports\Tap;
use Lens\Reports\Text;
use SpencerMortensen\RegularExpressions\Re;
use SpencerMortensen\ParallelProcessor\Shell\ShellSlave;
use SpencerMortensen\Paths\Paths;
use Throwable;

class Command
{
	/** @var integer */
	private static $maximumLineLength = 96;

	/** @var Logger */
	private $logger;

	/** @var Displayer */
	private $displayer;

	/** @var string */
	private $executable;

	/** @var array */
	private $options;

	/** @var array */
	private $values;

	/** @var boolean */
	private static $isInternalCommand;

	public function __construct()
	{
		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));

		$arguments = new Arguments();

		$this->logger = new Logger('lens');
		$this->displayer = new Displayer();
		$this->executable = $arguments->getExecutable();
		$this->options = $arguments->getOptions();
		$this->values = $arguments->getValues();

		$this->run();

		restore_exception_handler();
		restore_error_handler();
	}

	// lens --internal-coverage=... # INTERNAL: get code coverage
	// lens --internal-test=... # INTERNAL: get test results
	// lens --version  # get the installed version of Lens
	// lens --report=$report --coverage=$coverage $path ...  # run the specified tests
	private function run()
	{
		$this->getInternalCoverage() ||
		$this->getInternalTest() ||
		$this->getVersion() ||
		$this->getRunner();
	}

	private function getInternalCoverage()
	{
		if (!$this->getArguments('internal-coverage', $arguments)) {
			return false;
		}

		list($srcDirectory, $relativePaths, $autoloadPath) = $arguments;
		$job = new CoverageJob($this->executable, $srcDirectory, $relativePaths, $autoloadPath, $code, $coverage);

		$slave = new ShellSlave($job);
		$slave->run();

		return true;
	}

	private function getInternalTest()
	{
		if (!$this->getArguments('internal-test', $arguments)) {
			return false;
		}

		list($srcDirectory, $autoloadPath, $namespace, $uses, $prePhp, $script, $postPhp) = $arguments;
		$job = new TestJob($this->executable, $srcDirectory, $autoloadPath, $namespace, $uses, $prePhp, $script, $postPhp, $results, $coverage);

		$slave = new ShellSlave($job);
		$slave->run();

		return true;
	}

	private function getArguments($key, &$arguments)
	{
		if (!isset($this->options[$key])) {
			return false;
		}

		$data = $this->options[$key];
		$decoded = base64_decode($data);
		$decompressed = gzinflate($decoded);
		$arguments = unserialize($decompressed);

		return true;
	}

	private function getVersion()
	{
		if (!isset($this->options['version'])) {
			return false;
		}

		echo "lens 0.0.42\n";

		return true;
	}

	private function getRunner()
	{
		$reportType = $this->getReportType();
		$coverageType = $this->getCoverageType();
		$paths = $this->values;

		$filesystem = new Filesystem();
		$platform = Paths::getPlatformPaths();
		$settingsFile = new IniFile($filesystem);
		$settings = new Settings($settingsFile, $this->logger);
		$browser = new Browser($filesystem, $platform);
		$parser = new SuiteParser();
		$processor = new Processor();
		$evaluator = new Evaluator($this->executable, $filesystem, $processor);
		$verifier = new Verifier();
		$report = $this->getReport($reportType);

		$web = new Web($filesystem);

		$runner = new Runner($settings, $filesystem, $platform, $browser, $parser, $evaluator, $verifier, $report, $web);
		$runner->run($paths);
	}

	private function getReportType()
	{
		$options = $this->options;
		$type = &$options['report'];

		switch ($type) {
			// TODO: case 'xunit'

			case 'tap':
				return 'tap';

			default:
				return 'text';
		}
	}

	private function getReport($type)
	{
		if ($type === 'text') {
			return new Text();
		}

		return new Tap();
	}

	private function getCoverageType()
	{
		$options = $this->options;
		$type = &$options['coverage'];

		switch ($type) {
			// TODO: case 'none'
			// TODO: case 'clover'
			// TODO: case 'crap4j'
			// TODO: case 'text'

			default:
				return 'html';
		}

	}

	public function errorHandler($level, $message, $file, $line)
	{
		throw LensException::error($level, trim($message), $file, $line);
	}

	/**
	 * @param Throwable|Exception $exception
	 */
	public function exceptionHandler($exception)
	{
		try {
			throw $exception;
		} catch (LensException $throwable) {
		} catch (Throwable $throwable) {
			$exception = LensException::exception($throwable);
		} catch (Exception $throwable) {
			$exception = LensException::exception($throwable);
		}

		$severity = $exception->getSeverity();
		$code = $exception->getCode();
		$message = $exception->getMessage();
		$data = $exception->getData();

		$output = $this->getSyslogText($severity, $code, $message, $data);
		$this->logger->log($severity, $output);

		if (self::$isInternalCommand !== true) {
			$help = $exception->getHelp();

			$output = $this->getStderrText($severity, $code, $message, $help, $data);
			file_put_contents("php://stderr", "{$output}\n");
		}

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
			case LensException::SEVERITY_NOTICE:
				return 'Note';

			case LensException::SEVERITY_WARNING:
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

	public static function setIsInternalCommand($isInternalCommand)
	{
		self::$isInternalCommand = $isInternalCommand;
	}
}
