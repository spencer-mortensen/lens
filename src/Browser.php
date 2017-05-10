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

class Browser
{
	/** @var Filesystem */
	private $filesystem;

	/** @var array */
	private $tests;

	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
		$this->tests = array();
	}

	public function browse($directory)
	{
		$this->readDirectory($directory, '');

		return $this->tests;
	}

	private function readDirectory($absolutePath, $relativePath)
	{
		$files = @scandir($absolutePath, SCANDIR_SORT_NONE);

		if ($files === false) {
			throw Exception::invalidTestsDirectory($absolutePath);
		}

		foreach ($files as $file) {
			if (($file === '.') || ($file === '..')) {
				continue;
			}

			$childAbsolutePath = "{$absolutePath}/{$file}";
			$childRelativePath = ltrim("{$relativePath}/{$file}", '/');

			if (is_dir($childAbsolutePath)) {
				$this->readDirectory($childAbsolutePath, $childRelativePath);
			} elseif (is_file($childAbsolutePath) && (substr($file, -4) === '.php')) {
				$this->readFile($childAbsolutePath, $childRelativePath);
			}
		}
	}

	private function readFile($absolutePath, $relativePath)
	{
		$contents = @file_get_contents($absolutePath);

		if (!is_string($contents)) {
			throw Exception::invalidTestFile($absolutePath);
		}

		$lines = explode("\n", "\n{$contents}");
		unset($lines[0]);

		$i = 1;

		if (self::getSuite($lines, $i, $relativePath, $suite)) {
			$this->tests[] = $suite;
		}

		return true;
	}

	private static function getSuite(array $lines, &$i, $file, &$suite)
	{
		if (!self::getPhpTag($lines, $i) ||
			!self::getFixture($lines, $i, $fixture) ||
			!self::getCases($lines, $i, $cases)
		) {
			return false;
		}

		$suite = array(
			'file' => $file,
			'fixture' => $fixture,
			'cases' => $cases
		);

		return true;
	}

	private static function getPhpTag(array $lines, &$i)
	{
		if (isset($lines[$i]) && ($lines[$i] === '<?php')) {
			++$i;
			return true;
		}

		return false;
	}

	private static function getCases($lines, &$i, &$cases)
	{
		$cases = array();

		while (self::getCase($lines, $i, $case)) {
			$cases[] = $case;
		};

		return 0 < count($cases);
	}

	private static function getFixture(array $lines, &$i, &$fixture)
	{
		$end = count($lines);
		$fixture = '';

		for (; $i <= $end; ++$i) {
			if (self::isCauseLabel($lines[$i])) {
				$fixture = trim($fixture);

				if (strlen($fixture) === 0) {
					$fixture = null;
				}

				return true;
			}

			$fixture .= $lines[$i] . "\n";
		}

		return false;
	}

	private static function getCase(array $lines, &$i, &$case)
	{
		if (!self::getCause($lines, $i, $begin, $cause) ||
			!self::getEffect($lines, $i, $effect)
		) {
			return false;
		}

		$case = array(
			'line' => $begin,
			'cause' => array(
				'code' => $cause
			),
			'effect' => array(
				'code' => $effect
			)
		);

		return true;
	}

	private static function getCause(array $lines, &$i, &$begin, &$cause)
	{
		$end = count($lines);

		if (($end < $i) || !self::isCauseLabel($lines[$i])) {
			return false;
		}

		$begin = $i;
		$cause = '';

		for (++$i; $i <= $end; ++$i) {
			if (self::isEffectlabel($lines[$i])) {
				$cause = trim($cause);
				return true;
			}

			$cause .= $lines[$i] . "\n";
		}

		return false;
	}

	private static function getEffect(array $lines, &$i, &$effect)
	{
		$end = count($lines);

		if (($end < $i) || !self::isEffectLabel($lines[$i])) {
			return false;
		}

		$effect = '';

		for (++$i; $i <= $end; ++$i) {
			if (self::isCauseLabel($lines[$i])) {
				break;
			}

			$effect .= $lines[$i] . "\n";
		}

		$effect = trim($effect);
		return true;
	}

	private static function isCauseLabel($line)
	{
		return $line === '// Cause';
	}

	private static function isEffectLabel($line)
	{
		return $line === '// Effect';
	}
}
