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

namespace Lens\Evaluator;

use Lens\Filesystem;
use Lens\Logger;

class Coverage
{
	/** @var Filesystem */
	private $filesystem;

	/** @var array */
	private $code;

	/** @var array */
	private $coverage;

	/** @var string */
	private $php;

	/** @var callable */
	private $onShutdown;

	/** @var Logger */
	private $logger;

	public function __construct(Filesystem $filesystem, Logger $logger)
	{
		$this->filesystem = $filesystem;
		$this->logger = $logger;
	}

	public function run($srcDirectory, array $relativePaths, $autoloaderPath, $onShutdown)
	{
		if (!function_exists('xdebug_start_code_coverage')) {
			return null;
		}

		$this->onShutdown = $onShutdown;

		$this->readCode($srcDirectory, $relativePaths);
		$this->readCoverage($srcDirectory, $relativePaths, $autoloaderPath);

		call_user_func($onShutdown);
	}

	private function readCode($srcDirectory, array $relativePaths)
	{
		$this->code = array();

		foreach ($relativePaths as $relativePath) {
			$absolutePath = "{$srcDirectory}/{$relativePath}";
			$contents = $this->filesystem->read($absolutePath);
			$this->code[$relativePath] = self::getLines($contents);
		}
	}

	private function readCoverage($srcDirectory, array $relativePaths, $autoloaderPath)
	{
		$rawCoverage = $this->getRawCoverage($srcDirectory, $relativePaths, $autoloaderPath);
		$this->coverage = self::getCleanCoverage($srcDirectory, $this->code, $rawCoverage);
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getCoverage()
	{
		return $this->coverage;
	}

	private static function getLines($text)
	{
		$pattern = self::getPattern('\\r?\\n');

		return preg_split($pattern, $text);
	}

	private function getRawCoverage($srcDirectory, array $relativePaths, $autoloaderPath)
	{
		$absolutePaths = self::getAbsolutePaths($srcDirectory, $relativePaths);

		$statements = self::getIncludeStatements($absolutePaths);

		if (is_string($autoloaderPath)) {
			$autoloaderPhp = self::getRequireStatement($autoloaderPath);
			array_unshift($statements, $autoloaderPhp);
		}

		$this->php = implode("\n", $statements);

		ini_set('display_errors', 'Off');
		// TODO: handle a fatal error caused by requiring a file:
		// register_shutdown_function($callable);
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

		$this->evaluate();

		$coverage = xdebug_get_code_coverage();
		xdebug_stop_code_coverage();

		return $coverage;
	}

	private static function getAbsolutePaths($baseDirectory, array $relativePaths)
	{
		$absolutePaths = array();

		foreach ($relativePaths as $relativePath) {
			$absolutePaths[] = "{$baseDirectory}/{$relativePath}";
		}

		return $absolutePaths;
	}

	private static function getRequireStatement($path)
	{
		$pathString = var_export($path, true);

		return "require {$pathString};";
	}

	private static function getIncludeStatements(array $paths)
	{
		$statements = array();

		foreach ($paths as $path) {
			$pathString = var_export($path, true);
			$statements[] = "include_once {$pathString};";
		}

		return $statements;
	}

	private function evaluate()
	{
		eval($this->php);
	}

	private static function getCleanCoverage($srcDirectory, array $code, array $coverage)
	{
		$output = array();

		foreach ($code as $relativePath => $fileCode) {
			$absolutePath = "{$srcDirectory}/{$relativePath}";
			$fileCoverage = &$coverage[$absolutePath];

			if (isset($fileCoverage)) {
				$output[$relativePath] = self::getCleanFileCoverage($fileCoverage, $fileCode);
			}
		}

		return $output;
	}

	private static function getCleanFileCoverage(array $coverage, array $code)
	{
		$output = array();

		foreach ($coverage as $lineNumber => $lineCoverage) {
			$lineCode = &$code[--$lineNumber];

			if (self::isTestableCode($lineCode)) {
				$output[] = $lineNumber;
			}
		}

		return $output;
	}

	private static function isTestableCode($text)
	{
		$text = trim($text);

		if (strlen(trim($text, '{}')) === 0) {
			return false;
		}

		if (substr($text, 0, 6) === 'class ') {
			return false;
		}

		return true;
	}

	private static function getPattern($expression, $flags = '')
	{
		$delimiter = "\x03";

		return "{$delimiter}{$expression}{$delimiter}{$flags}";
	}
}
