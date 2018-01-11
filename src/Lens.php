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
use Lens\Exceptions\TerminalMessage;
use Lens\Exceptions\LogMessage;
use Lens\Commands\Coverage;
use Lens\Commands\Runner;
use Lens\Commands\Test;
use Lens\Commands\Version;
use SpencerMortensen\Exceptions\Exceptions;
use Throwable;

class Lens
{
	/** @var Logger */
	private $logger;

	/** @var Arguments */
	private $arguments;

	// lens --internal-coverage=... # get code coverage (private)
	// lens --internal-test=... # get test results (private)
	// lens --version  # get the installed version of Lens
	// lens --report=$report --coverage=$coverage $path ...  # run the specified tests
	public function __construct()
	{
		// TODO: in the ShellClientProcess, pass through any STDERR text from the child process

		Exceptions::on(array($this, 'onError'));

		try {
			$this->logger = new Logger('lens');
			$this->arguments = new Arguments();

			if (!$this->run($stdout, $stderr, $exitCode)) {
				throw LensException::usage();
			}
		} catch (Throwable $exception) {
			list($stdout, $stderr, $exitCode) = $this->handleException($exception);
		} catch (Exception $exception) {
			list($stdout, $stderr, $exitCode) = $this->handleException($exception);
		}

		Exceptions::off();

		$this->send($stdout, $stderr, $exitCode);
	}

	private function run(&$stdout, &$stderr, &$exitCode)
	{
		return $this->runCoverage($stdout, $stderr, $exitCode) ||
			$this->runTest($stdout, $stderr, $exitCode) ||
			$this->runVersion($stdout, $stderr, $exitCode) ||
			$this->runRunner($stdout, $stderr, $exitCode);
	}

	private function runCoverage(&$stdout, &$stderr, &$exitCode)
	{
		$coverage = new Coverage($this->arguments);
		return $coverage->run($stdout, $stderr, $exitCode);
	}

	private function runTest(&$stdout, &$stderr, &$exitCode)
	{
		$test = new Test($this->arguments);
		return $test->run($stdout, $stderr, $exitCode);
	}

	private function runVersion(&$stdout, &$stderr, &$exitCode)
	{
		$version = new Version($this->arguments);
		return $version->run($stdout, $stderr, $exitCode);
	}

	private function runRunner(&$stdout, &$stderr, &$exitCode)
	{
		$runner = new Runner($this->arguments, $this->logger);
		return $runner->run($stdout, $stderr, $exitCode);
	}

	private function send($stdout, $stderr, $exitCode)
	{
		if ($stdout !== null) {
			file_put_contents('php://stdout', "{$stdout}\n");
		}

		if ($stderr !== null) {
			file_put_contents('php://stderr', "{$stderr}\n");
		}

		exit($exitCode);
	}

	/**
	 * @param Throwable|Exception $exception
	 */
	public function onError($exception)
	{
		list($stdout, $stderr, $exitCode) = $this->handleException($exception);

		$this->send($stdout, $stderr, $exitCode);
	}

	/**
	 * @param Throwable|Exception $exception
	 * @return array
	 */
	private function handleException($exception)
	{
		if (!($exception instanceof LensException)) {
			$exception = LensException::exception($exception);
		}

		$this->logException($exception);

		return $this->getOutput($exception);
	}

	private function logException(LensException $exception)
	{
		if ($this->logger === null) {
			return;
		}

		$message = new LogMessage($exception);

		$severity = $exception->getSeverity();
		$messageText = $message->getText();

		$this->logger->log($severity, $messageText);
	}

	/**
	 * @param LensException $exception
	 * @return array
	 */
	private function getOutput(LensException $exception)
	{
		$message = new TerminalMessage($exception);

		$stdout = null;
		$stderr = $message->getText();
		$exitCode = $exception->getCode();

		return array($stdout, $stderr, $exitCode);
	}
}
