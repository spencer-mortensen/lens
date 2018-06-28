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
use Exception;
use _Lens\Lens\Exceptions\TerminalMessage;
use _Lens\Lens\Exceptions\LogMessage;
use _Lens\Lens\Commands\LensSource;
use _Lens\Lens\Commands\LensCoverage;
use _Lens\Lens\Commands\LensRunner;
use _Lens\Lens\Commands\LensTest;
use _Lens\Lens\Commands\LensVersion;
use _Lens\SpencerMortensen\Exceptions\Exceptions;

class Lens
{
	/** @var Logger */
	private $logger;

	/** @var Arguments */
	private $arguments;

	// lens --version  # get the installed version of Lens
	// lens [$path ...] --clover=... --coverage=... --tap=... --text=... --xunit=...  # run the specified tests
	// lens --internal-coverage=... # get code coverage (private)
	// lens --internal-test=... # get test results (private)
	// lens --internal-source=... # generate the source-code cache (private)
	public function __construct()
	{
		$this->logger = new Logger('lens');
		$this->arguments = new Arguments();

		try {
			Exceptions::on();
			Exceptions::setHandler([$this, 'onError']);

			if (!$this->run($stdout, $stderr, $exitCode)) {
				throw LensException::usage();
			}

			$this->send($stdout, $stderr, $exitCode);
		} finally {
			Exceptions::unsetHandler();
			Exceptions::off();
		}
	}

	private function run(&$stdout, &$stderr, &$exitCode)
	{
		return $this->runCoverage($stdout, $stderr, $exitCode) ||
			$this->runTest($stdout, $stderr, $exitCode) ||
			$this->runSource($stdout, $stderr, $exitCode) ||
			$this->runVersion($stdout, $stderr, $exitCode) ||
			$this->runRunner($stdout, $stderr, $exitCode);
	}

	private function runCoverage(&$stdout, &$stderr, &$exitCode)
	{
		$coverage = new LensCoverage($this->arguments);
		return $coverage->run($stdout, $stderr, $exitCode);
	}

	private function runTest(&$stdout, &$stderr, &$exitCode)
	{
		$test = new LensTest($this->arguments);
		return $test->run($stdout, $stderr, $exitCode);
	}

	private function runSource(&$stdout, &$stderr, &$exitCode)
	{
		$cache = new LensSource($this->arguments);
		return $cache->run($stdout, $stderr, $exitCode);
	}

	private function runVersion(&$stdout, &$stderr, &$exitCode)
	{
		$version = new LensVersion($this->arguments);
		return $version->run($stdout, $stderr, $exitCode);
	}

	private function runRunner(&$stdout, &$stderr, &$exitCode)
	{
		$runner = new LensRunner($this->arguments);
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
	 * @param Exception|Error $exception
	 */
	public function onError($exception)
	{
		if (!($exception instanceof LensException)) {
			$exception = LensException::exception($exception);
		}

		$this->logException($exception);

		list($stdout, $stderr, $exitCode) = $this->getOutput($exception);

		$this->send($stdout, $stderr, $exitCode);
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

		return [$stdout, $stderr, $exitCode];
	}
}
