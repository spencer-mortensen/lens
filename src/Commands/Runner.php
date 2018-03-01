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

namespace Lens\Commands;

use Lens\Arguments;
use Lens\Browser;
use Lens\Evaluator\Evaluator;
use Lens\Filesystem;
use Lens\Finder;
use Lens\LensException;
use Lens\Reports\Tap;
use Lens\Reports\Text;
use Lens\Reports\XUnit;
use Lens\SuiteParser;
use Lens\Summarizer;
use Lens\Web;
use SpencerMortensen\Parser\ParserException;
use SpencerMortensen\Paths\Paths;

class Runner implements Command
{
	/** @var Arguments */
	private $arguments;

	/** @var Paths */
	private $paths;

	/** @var Filesystem */
	private $filesystem;

	/** @var Finder */
	private $finder;

	public function __construct(Arguments $arguments)
	{
		$this->arguments = $arguments;
		$this->paths = Paths::getPlatformPaths();
		$this->filesystem = new Filesystem();
		$this->finder = new Finder($this->paths, $this->filesystem);
	}

	public function run(&$stdout, &$stderr, &$exitCode)
	{
		$options = $this->arguments->getOptions();
		$paths = $this->arguments->getValues();
		$report = $this->getReport($options);

		// TODO: if there are any options other than "report", then throw a usage exception

		$this->finder->find($paths);
		$executable = $this->arguments->getExecutable();
		$evaluator = new Evaluator($executable, $this->filesystem);

		$srcPath = $this->finder->getSrc();
		$autoloadPath = $this->finder->getAutoload();
		$testsPath = $this->finder->getTests();

		$suites = $this->getSuites($paths);

		list($suites, $code, $coverage) = $evaluator->run($srcPath, $autoloadPath, $suites);

		$project = array(
			'name' => 'Lens', // TODO: let the user provide the project name in the configuration file
			'suites' => $this->useRelativePaths($testsPath, $suites)
		);

		$summarizer = new Summarizer();
		$summarizer->summarize($project);

		$stdout = $report->getReport($project);
		$stderr = null;

		if (isset($code, $coverage)) {
			$web = new Web($this->filesystem);
			$coveragePath = $this->finder->getCoverage();
			$web->coverage($srcPath, $coveragePath, $code, $coverage);
		}

		$isSuccessful = ($project['summary']['failed'] === 0);

		if ($isSuccessful) {
			$exitCode = 0;
		} else {
			$exitCode = LensException::CODE_FAILURES;
		}

		return true;
	}

	private function getReport(array $options)
	{
		$type = &$options['report'];

		if ($type === null) {
			return new Text();
		}

		switch ($type) {
			case 'xunit':
				return new XUnit();

			case 'tap':
				return new Tap();

			default:
				throw LensException::invalidReport($type);
		}
	}

	/*
	private function getCoverage()
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
	*/

	private function getSuites(array $paths)
	{
		$browser = new Browser($this->filesystem, $this->paths);
		$files = $browser->browse($paths);

		$suites = array();
		$parser = new SuiteParser();

		foreach ($files as $path => $contents) {
			try {
				$suites[$path] = $parser->parse($contents);
			} catch (ParserException $exception) {
				throw LensException::invalidTestsFileSyntax($path, $contents, $exception);
			}
		}

		return $suites;
	}

	private function useRelativePaths($testsDirectory, array $input)
	{
		$output = array();

		foreach ($input as $absolutePath => $value) {
			$relativePath = $this->paths->getRelativePath($testsDirectory, $absolutePath);
			$output[$relativePath] = $value;
		}

		return $output;
	}
}
