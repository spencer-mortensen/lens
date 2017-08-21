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

use TestPhp\Archivist\Archives\Archive;
use TestPhp\Archivist\Archives\ObjectArchive;

class Console
{
	private static $maxWidth = 96;

	public function summarize(array $suites)
	{
		$passedTestsCount = 0;
		$failedTests = array();

		foreach ($suites as $suite) {
			foreach ($suite['tests'] as $test) {
				self::summarizeTest($test, $passedTestsCount, $failedTests);
			}
		}

		$output = array();

		if (0 < $passedTestsCount) {
			$output[] = self::showPassedTests($passedTestsCount);
		}

		if (0 < count($failedTests)) {
			$output[] = self::showFailedTests($failedTests);
		}

		return implode("\n", $output) . "\n";
	}

	private static function summarizeTest(array $test, &$passedTestsCount, array &$failedTests)
	{
		$subject = $test['subject'];

		foreach ($test['cases'] as $case) {
			$expected = $case['expected']['state'];
			$actual = $case['actual']['state'];

			if (
				self::isBroken($expected, '-', $issues) ||
				self::isBroken($actual, '+', $issues) ||
				self::isDifferent($expected, $actual, $issues)
			) {
				$failedTests[] = array($subject, $case['text'], $issues);
			} else {
				++$passedTestsCount;
			}
		}
	}

	private static function isBroken(array $state, $label, &$issues)
	{
		if ($state['exit'] !== 255) {
			return false;
		}

		$state = self::flatten($state);

		if ($state['fatalError'] !== null) {
			$issues = self::getDifferenceText($label, $state['fatalError']);
		} elseif ($state['stderr'] !== null) {
			$issues = self::getDifferenceText($label, $state['stderr']);
		} else {
			$issues = self::getDifferenceText($label, $state['exit']);
		}

		return true;
	}

	private static function isDifferent(array $a, array $b, &$issues)
	{
		$a = self::flatten($a);
		$b = self::flatten($b);

		self::ignoreTestVariables($a['variables'], $b['variables']);
		self::diff($a, $b);

		if ($a === $b) {
			return false;
		}

		$output = array();

		self::getDifferencesValue($a['exit'], $b['exit'], $output);
		self::getDifferencesValue($a['stderr'], $b['stderr'], $output);
		self::getDifferencesValue($a['fatalError'], $b['fatalError'], $output);
		self::getDifferencesMap($a['errors'], $b['errors'], $output);
		self::getDifferencesValue($a['exception'], $b['exception'], $output);
		self::getDifferencesMap($a['calls'], $b['calls'], $output);
		self::getDifferencesValue($a['output'], $b['output'], $output);
		self::getDifferencesMap($a['constants'], $b['constants'], $output);
		self::getDifferencesMap($a['globals'], $b['globals'], $output);
		self::getDifferencesMap($a['variables'], $b['variables'], $output);

		$issues = implode("\n", $output);

		return true;
	}

	private static function getDifferencesMap($a, $b, &$output)
	{
		$keys = self::getKeys($a, $b);

		foreach ($keys as $key) {
			if (array_key_exists($key, $a)) {
				$output[] = self::getDifferenceText('-', $a[$key]);
			}

			if (array_key_exists($key, $b)) {
				$output[] = self::getDifferenceText('+', $b[$key]);
			}
		}
	}

	private static function getKeys(array $a, array $b)
	{
		$keys = array();

		foreach ($a as $key => $value) {
			$keys[$key] = $key;
		}

		foreach ($b as $key => $value) {
			$keys[$key] = $key;
		}

		sort($keys, SORT_NATURAL);

		return $keys;
	}

	private static function getDifferencesValue($a, $b, &$output)
	{
		if ($a !== null) {
			$output[] = self::getDifferenceText('-', $a);
		}

		if ($b !== null) {
			$output[] = self::getDifferenceText('+', $b);
		}
	}

	private static function getDifferenceText($label, $input)
	{
		return " {$label} " . self::wrap($input, '', '   ');
	}

	private static function wrap($string, $outerPadding, $innerPadding)
	{
		$lines = explode("\n", $string);

		foreach ($lines as &$line) {
			if (0 < strlen($line)) {
				$chunks = str_split($line, self::$maxWidth);
				$line = $outerPadding . implode("\n{$innerPadding}", $chunks);
			}
		}

		return implode("\n", $lines);
	}

	private static function flatten(array $state)
	{
		$displayer = new Displayer();
		$objectNames = self::getObjectNames($state['variables']);

		self::flattenVariables($state['variables'], $displayer);
		self::flattenGlobals($state['globals'], $displayer);
		self::flattenConstants($state['constants'], $displayer);
		self::flattenOutput($state['output'], $displayer);
		self::flattenCalls($state['calls'], $objectNames, $displayer);
		self::flattenException($state['exception'], $displayer);
		self::flattenErrors($state['errors'], $displayer);
		self::flattenError($state['fatalError'], $displayer);
		self::flattenStderr($state['stderr'], $displayer);
		self::flattenExit($state['exit'], $displayer);

		return $state;
	}

	private static function getObjectNames(array $variables)
	{
		$names = array();

		foreach ($variables as $name => $archive) {
			if (is_object($archive) && ($archive->getArchiveType() === Archive::TYPE_OBJECT)) {
				$id = $archive->getId();
				$names[$id] = $name;
			}
		}

		return $names;
	}

	private static function flattenVariables(array &$variables, Displayer $displayer)
	{
		foreach ($variables as $name => &$value) {
			$value = self::getVariableText($name, $displayer->display($value));
		}
	}

	private static function getVariableText($name, $value)
	{
		return "\${$name} = {$value};";
	}

	private static function flattenGlobals(array &$variables, Displayer $displayer)
	{
		foreach ($variables as $name => &$value) {
			$value = self::getGlobalText($name, $displayer->display($value));
		}
	}

	private static function getGlobalText($name, $value)
	{
		return "\$GLOBALS['{$name}'] = {$value};";
	}

	private static function flattenConstants(array &$constants, Displayer $displayer)
	{
		foreach ($constants as $name => &$value) {
			$value = self::getConstantText($name, $displayer->display($value));
		}
	}

	private static function getConstantText($name, $value)
	{
		return "define('{$name}', {$value});";
	}

	private static function flattenOutput(&$value, Displayer $displayer)
	{
		if ($value === '') {
			$value = null;
		} else {
			$value = self::getOutputText($displayer->display($value));
		}
	}

	private static function getOutputText($value)
	{
		return "echo {$value};";
	}

	private static function flattenCalls(array &$calls, array $objectNames, Displayer $displayer)
	{
		foreach ($calls as &$call) {
			$call = self::getCallText($call, $objectNames, $displayer);
		}
	}

	private static function getCallText(array $call, array $names, Displayer $displayer)
	{
		list($callableArchive, $argumentsArchive) = $call;
		list($objectArchive, $method) = $callableArchive;

		$objectText = self::getObjectText($objectArchive, $names, $displayer);
		$argumentsText = self::getArgumentsText($argumentsArchive, $displayer);

		return "{$objectText}->{$method}({$argumentsText});";
	}

	private static function getObjectText(ObjectArchive $objectArchive, array $names, Displayer $displayer)
	{
		$id = $objectArchive->getId();

		if (isset($names[$id])) {
			return "\${$names[$id]}";
		}

		return $displayer->display($objectArchive);
	}

	private static function getArgumentsText(array $argumentsArchive, Displayer $displayer)
	{
		if (count($argumentsArchive) === 0) {
			return '';
		}

		$output = array();

		foreach ($argumentsArchive as $argumentValueArchive) {
			$output[] = $displayer->display($argumentValueArchive);
		}

		return implode(', ', $output);
	}

	private static function flattenException(&$exception, Displayer $displayer)
	{
		if ($exception === null) {
			return;
		}

		/** @var ObjectArchive $exception */
		$properties = $exception->getProperties();

		unset(
			$properties['Exception']['file'],
			$properties['Exception']['line'],
			$properties['Exception']['previous'],
			$properties['Exception']['trace'],
			$properties['Exception']['xdebug_message']
		);

		unset(
			$properties['Error']['file'],
			$properties['Error']['line'],
			$properties['Error']['previous'],
			$properties['Error']['trace'],
			$properties['Error']['xdebug_message']
		);

		$exception->setProperties($properties);

		$exception = self::getExceptionText($displayer->display($exception));
	}

	private static function getExceptionText($value)
	{
		return "throw {$value};";
	}

	private static function flattenErrors(array &$errors, Displayer $displayer)
	{
		foreach ($errors as &$error) {
			self::flattenError($error, $displayer);
		}
	}

	private static function flattenError(&$error, Displayer $displayer)
	{
		if ($error === null) {
			return;
		}

		// TODO: replace this "getcwd" function call
		$currentDirectory = getcwd();

		list($level, $message, $file, $line) = $error;

		$nameText = self::getErrorLevelName($level);

		$output = "{$nameText}: ";

		if (is_string($file)) {
			$file = rtrim(self::getRelativePath($currentDirectory, $file), '/');
			$fileText = self::getFilePosition($file, $line);

			$output .= "{$fileText}: ";
		}

		$output .= $displayer->display($message);

		$error = $output;
	}

	private static function flattenStderr(&$stderr, Displayer $displayer)
	{
		if (strlen($stderr) === 0) {
			$stderr = null;
			return;
		}

		$valueText = $displayer->display($stderr);
		$stderr = "fwrite(STDERR, {$valueText});";
	}

	private static function flattenExit(&$exit, Displayer $displayer)
	{
		if ($exit === 0) {
			$exit = null;
			return;
		}

		$valueText = $displayer->display($exit);
		$exit = "exit({$valueText});";
	}

	private static function getErrorLevelName($level)
	{
		switch ($level)
		{
			case E_ERROR: return 'E_ERROR';
			case E_WARNING: return 'E_WARNING';
			case E_PARSE: return 'E_PARSE';
			case E_NOTICE: return 'E_NOTICE';
			case E_CORE_ERROR: return 'E_CORE_ERROR';
			case E_CORE_WARNING: return 'E_CORE_WARNING';
			case E_COMPILE_ERROR: return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING: return 'E_COMPILE_WARNING';
			case E_USER_ERROR: return 'E_USER_ERROR';
			case E_USER_WARNING: return 'E_USER_WARNING';
			case E_USER_NOTICE: return 'E_USER_NOTICE';
			case E_STRICT: return 'E_STRICT';
			case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: return 'E_DEPRECATED';
			case E_USER_DEPRECATED: return 'E_USER_DEPRECATED';
			case E_ALL: return 'E_ALL';
			default: return null;
		}
	}

	private static function getRelativePath($currentPath, $targetPath)
	{
		$currentTrail = self::getTrail($currentPath);
		$targetTrail = self::getTrail($targetPath);

		$n = min(count($currentTrail), count($targetTrail));

		for ($i = 0; ($i < $n) && ($currentTrail[$i] === $targetTrail[$i]); ++$i);

		$relativeDirectory = str_repeat('../', count($currentTrail) - $i);

		if (0 < count($targetTrail)) {
			$relativeDirectory .= implode('/', array_slice($targetTrail, $i)) . '/';
		}

		return $relativeDirectory;
	}

	private static function getTrail($path)
	{
		if (strlen($path) === 0) {
			return array();
		}

		return explode('/', $path);
	}

	private static function ignoreTestVariables(array &$expected, array &$actual)
	{
		$names = array_keys($actual);

		foreach ($names as $name) {
			if (!array_key_exists($name, $expected)) {
				unset($actual[$name]);
			}
		}
	}

	private static function diff(&$a, &$b)
	{
		self::diffMap($a['variables'], $b['variables']);
		self::diffMap($a['globals'], $b['globals']);
		self::diffMap($a['constants'], $b['constants']);
		self::diffValue($a['output'], $b['output']);
		self::diffMap($a['calls'], $b['calls']);
		self::diffValue($a['exception'], $b['exception']);
		self::diffMap($a['errors'], $b['errors']);
		self::diffValue($a['fatalError'], $b['fatalError']);
	}

	private static function diffMap(&$a, &$b)
	{
		$x = array_intersect_assoc($a, $b);
		$a = array_diff_key($a, $x);
		$b = array_diff_key($b, $x);
	}

	private static function diffValue(&$a, &$b)
	{
		if ($a === $b) {
			$a = $b = null;
		}
	}

	private static function showPassedTests($count)
	{
		return "Passed tests: {$count}";
	}

	private static function showFailedTests(array $tests)
	{
		$count = count($tests);

		list($subject, $body, $issues) = current($tests);
		$testBody = self::getTest($subject, $body, $issues);

		return "Failed tests: {$count}\n\n{$testBody}";
	}

	private static function getTest($subject, $body, $issues)
	{
		$text = "// Test\n{$subject}\n\n" .
			"{$body}\n\n" .
			"// Issues\n";

		return self::wrap($text, '   ', '   ') . $issues;
	}

	private static function getFilePosition($file, $line)
	{
		$displayer = new Displayer();

		if (is_string($file)) {
			$fileText = $displayer->display($file);
		} else {
			$fileText = null;
		}

		$lineText = (string)$line;

		return "{$fileText} (line {$lineText})";
	}
}
