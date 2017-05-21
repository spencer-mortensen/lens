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

class Runner
{
	/** @var Browser */
	private $browser;

	/** @var Evaluator */
	private $evaluator;

	/** @var Console */
	private $console;

	/** @var Web */
	private $web;

	public function __construct(Browser $browser, Evaluator $evaluator, Console $console, Web $web)
	{
		$this->browser = $browser;
		$this->evaluator = $evaluator;
		$this->console = $console;
		$this->web = $web;

		set_exception_handler(array($this, 'exceptionHandler'));
	}

	public function run($codeDirectory, $testsDirectory, $currentDirectory)
	{
        // TODO: require a valid tests directory
        // TODO: see if the corresponding "coverage" directory can be safely overwritten

		// TODO: require a valid code directory (must be a directory and contain at least one PHP file)

		$testsTestsDirectory = "{$testsDirectory}/tests";
		$coverageDirectory = "{$testsDirectory}/coverage";

		$tests = $this->browser->browse($testsTestsDirectory);
		$results = $this->evaluator->evaluate($tests, $testsDirectory, $codeDirectory);

		echo $this->console->summarize($testsTestsDirectory, $currentDirectory, $results['tests']);
		$this->web->coverage($codeDirectory, $coverageDirectory, $results['coverage']);
	}

	/**
	 * @param \Exception|\Throwable $exception
	 */
	public function exceptionHandler($exception)
	{
		$code = $exception->getCode();
		$message = $exception->getMessage();

		file_put_contents('php://stderr', "Error {$code}: {$message}\n");
		exit($code);
	}
}
