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

class Console
{
	public function summarize($testsDirectory, $currentDirectory, array $suites)
	{
		$passedTestsCount = 0;
		$failedTests = array();
		$brokenTests = array();

		$relativeTestsDirectory = self::getRelativePath($currentDirectory, $testsDirectory);

		foreach ($suites as $suite) {
			foreach ($suite['cases'] as $case) {
				if (self::isBrokenTest($case['effect'])) {
					$brokenTests[] = self::getBrokenTest($relativeTestsDirectory, $suite['file'], $case);
					continue;
				}

				$actual = self::flatten($case['cause']['results']);
				$expected = self::flatten($case['effect']['results']);
				self::diff($actual, $expected);

				if (self::isPassingTest($actual, $expected)) {
					++$passedTestsCount;
				} else {
					// TODO: prevent the "$actual['results'] === null" situation
					$failedTests[] = self::getFailedTest($relativeTestsDirectory, $suite['file'], $case, $actual, $expected);
				}
			}
		}

		$output = array();

		if (0 < $passedTestsCount) {
			$output[] = self::showPassedTests($passedTestsCount);
		}

		if (0 < count($failedTests)) {
			$output[] = self::showTests('FAILED', $failedTests);
		}

		if (0 < count($brokenTests)) {
			$output[] = self::showTests('BROKEN', $brokenTests);
		}

		return implode("\n\n", $output) . "\n";
	}

	private static function isBrokenTest(array $effect)
	{
		return ($effect['exit'] !== 0) || isset($effect['results']['fatalError']);
	}

	private static function getBrokenTest($testsDirectory, $file, $test)
	{
		$text = self::indent($test['text'], '   ');

		$issues = self::getBrokenTestIssues($test['effect']['results'], $test['effect']['exit']);
		$reference = self::getReference($testsDirectory, $file, $test['line']);

		return "{$text}\n\n   // Issues\n{$issues}\n\n{$reference}";
	}

	private static function getBrokenTestIssues($results, $exit)
	{
		$output = array();

		$displayer = new Displayer();

		self::flattenError($results['fatalError'], $displayer);
		self::flattenExit($exit, $displayer);

		if ($results['fatalError'] !== null) {
			$output[] = ' + ' . $results['fatalError'];
		}

		if ($exit !== null) {
			$output[] = ' + ' . $exit;
		}

		return implode("\n", $output);
	}

	private static function flattenExit(&$exit, Displayer $displayer)
	{
		if ($exit === 0) {
			$exit = null;
		}

		$exit = self::getExitText($displayer->display($exit));
	}

	private static function getExitText($value)
	{
		return "exit({$value});";
	}

	private static function getReference($testsDirectory, $file, $line)
	{
		$filePosition = self::getFilePosition($testsDirectory . $file, $line);
		return self::indent("See it: {$filePosition}", '   ');
	}

	private static function flatten($results)
	{
		if (!is_array($results)) {
			return null;
		}

		$displayer = new Displayer();
		$objectNames = self::getObjectNames($results['variables']);

		self::flattenVariables($results['variables'], $displayer);
		self::flattenGlobals($results['globals'], $displayer);
		self::flattenConstants($results['constants'], $displayer);
		self::flattenOutput($results['output'], $displayer);
		self::flattenCalls($results['calls'], $objectNames, $displayer);
		self::flattenException($results['exception'], $displayer);
		self::flattenErrors($results['errors'], $displayer);
		self::flattenError($results['fatalError'], $displayer);

		return $results;
	}

	private static function getObjectNames(array $variables)
	{
		$names = array();

		foreach ($variables as $name => $archive) {
			if (!is_array($archive)) {
				continue;
			}

			list($type, $value) = each($archive);

			if ($type !== Archivist::TYPE_OBJECT) {
				continue;
			}

			$id = $value[0];

			$names[$id] = $name;
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
		list($callableArchive, $argumentsArchive, $resultArchive) = current($call);
		list($objectArchive, $method) = current($callableArchive);

		$objectText = self::getObjectText($objectArchive, $names, $displayer);

		$arguments = current($argumentsArchive);
		$argumentsText = self::getArgumentsText($arguments, $displayer);

		$resultText = self::getResultText($resultArchive, $displayer);

		return "{$objectText}->{$method}({$argumentsText});{$resultText}";
	}

	private static function getObjectText(array $object, array $names, Displayer $displayer)
	{
		list($id) = current($object);

		if (isset($names[$id])) {
			return "\${$names[$id]}";
		}

		return $displayer->display($object);
	}

	private static function getArgumentsText(array $arguments, Displayer $displayer)
	{
		if (count($arguments) === 0) {
			return '';
		}

		$output = array();

		foreach ($arguments as $argumentValue) {
			$output[] = $displayer->display($argumentValue);
		}

		return implode(', ', $output);
	}

	private static function getResultText($resultArchive, Displayer $displayer)
	{
		if ($resultArchive === null) {
			return null;
		}

		$resultText = $displayer->display($resultArchive);
		return " // {$resultText}";
	}

	private static function flattenException(&$exception, Displayer $displayer)
	{
		if ($exception === null) {
			return;
		}

		$object = &$exception[Archivist::TYPE_OBJECT];

		unset(
			$object[2]['file'],
			$object[2]['line'],
			$object[2]['trace'],
			$object[2]['xdebug_message']
		);

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

		list($level, $message, $file, $line) = current($error);

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

	private static function isPassingTest(&$cause, &$effect)
	{
		return $cause === $effect;
	}

	private static function getFailedTest($testsDirectory, $file, $test, $causeResults, $effectResults)
	{
		$text = self::indent($test['text'], '   ');

		$issues = self::getIssues($effectResults, $causeResults);

		$file = $testsDirectory . $file;
		$line = $test['line'];

		// TODO: use the "getReference" function
		$filePosition = self::getFilePosition($file, $line);
		$seeIt = self::indent("See it: {$filePosition}", '   ');

		return "{$text}\n\n   // Issues\n{$issues}\n\n{$seeIt}";
	}

	private static function getIssues($a, $b)
	{
		$output = array();

		self::getDifferencesValue($a['fatalError'], $b['fatalError'], $output);
		self::getDifferencesMap($a['errors'], $b['errors'], $output);
		self::getDifferencesValue($a['exception'], $b['exception'], $output);
		self::getDifferencesValue($a['output'], $b['output'], $output);
		self::getDifferencesMap($a['calls'], $b['calls'], $output);
		self::getDifferencesMap($a['constants'], $b['constants'], $output);
		self::getDifferencesMap($a['globals'], $b['globals'], $output);
		self::getDifferencesMap($a['variables'], $b['variables'], $output);

		return implode("\n", $output);
	}

	private static function getDifferencesMap($a, $b, &$output)
	{
		$keys = self::getKeys($a, $b);

		foreach ($keys as $key) {
			if (array_key_exists($key, $a)) {
				$output[] = ' - ' . $a[$key];
			}

			if (array_key_exists($key, $b)) {
				$output[] = ' + ' . $b[$key];
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
			$output[] = ' - ' . $a;
		}

		if ($b !== null) {
			$output[] = ' + ' . $b;
		}
	}

	private static function showPassedTests($count)
	{
		return "Passed tests: {$count}";
	}

	private static function showTests($title, array $tests)
	{
		$title = self::getTestsSectionTitle($title, count($tests));

		array_unshift($tests, $title);

		return implode("\n\n", $tests);
	}

	private static function getTestsSectionTitle($label, $count)
	{
		$output = "{$label} TEST";

		if (1 < $count) {
			$output .= 'S';
		}

		$output .= ':';

		return $output;
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

	private static function indent($text, $padding)
	{
		// TODO: check this:
		return $padding . str_replace("\n", "\n" . $padding, $text);
	}
}
