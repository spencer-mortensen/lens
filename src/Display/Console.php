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

namespace TestPhp\Display;

use TestPhp\Archivist;

class Console
{
	public function summarize($testsDirectory, $currentDirectory, array $suites)
	{
		$passedTestsCount = 0;
		$failedTests = array();
		$brokenTests = array();

		foreach ($suites as $suite) {
			foreach ($suite['cases'] as $case) {
				if (self::isBrokenTest($case['cause'], $case['effect'])) {
					$brokenTests[] = self::getTest($suite['file'], $suite['fixture'], $case);
				} elseif (self::isPassingTest($case['cause']['results'], $case['effect']['results'])) {
					++$passedTestsCount;
				} else {
					$failedTests[] = self::getTest($suite['file'], $suite['fixture'], $case);
				}
			}
		}

		$relativeTestsDirectory = self::getRelativePath($currentDirectory, $testsDirectory);

		$output = array();

		if (0 < $passedTestsCount) {
			$output[] = self::showPassedTests($passedTestsCount);
		}

		if (0 < count($failedTests)) {
			$output[] = self::showFailedTests($relativeTestsDirectory, $failedTests);
		}

		if (0 < count($brokenTests)) {
			$output[] = self::showBrokenTests($relativeTestsDirectory, $brokenTests);
		}

		return implode("\n\n", $output) . "\n";
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

	private static function isBrokenTest($cause, $effect)
	{
		return ($cause['exit'] !== 0)
			|| ($effect['exit'] !== 0)
			|| !isset($cause['results'], $effect['results']);
	}

	private static function isPassingTest($cause, $effect)
	{
		$comparer = new Comparer();

		return $comparer->isSameArray($cause['variables'], $effect['variables'])
			&& $comparer->isSameArray($cause['globals'], $effect['globals'])
			&& $comparer->isSameArray($cause['constants'], $effect['constants'])
			&& $comparer->isSame($cause['output'], $effect['output'])
			&& $comparer->isSameArray($cause['calls'], $effect['calls']) // TODO: expect calls to be pre-sorted in a canonical order
			&& $comparer->isSame($cause['exception'], $effect['exception'])
			&& $comparer->isSameArray($cause['errors'], $effect['errors']); // TODO: consider file and line numbers
	}

	private static function getTest($file, $fixture, $case)
	{
		return array(
			'file' => $file,
			'line' => $case['line'],
			'fixture' => $fixture,
			'cause' => $case['cause'],
			'effect' => $case['effect']
		);
	}

	private static function showPassedTests($count)
	{
		return "Passed tests: {$count}";
	}

	private static function showFailedTests($testsDirectory, array $tests)
	{
		$output = array(
			self::getTestsSectionTitle('FAILED', count($tests))
		);

		foreach ($tests as $test) {
			$output[] = self::showTest($testsDirectory, $test);
		}

		return implode("\n\n", $output);
	}

	private static function showBrokenTests($testsDirectory, array $tests)
	{
		$output = array(
			self::getTestsSectionTitle('BROKEN', count($tests))
		);

		foreach ($tests as $test) {
			$output[] = self::showTest($testsDirectory, $test);
		}

		return implode("\n\n", $output);
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

	private static function showTest($testsDirectory, array $test)
	{
		$code = self::getCode($test['cause']['code']);

		$file = $testsDirectory . $test['file'];
		$line = $test['line'];

		$comparer = new Comparer();
		$displayer = new Displayer();
		$table = new Table();
		$table->addRow('Actual', 'Expected');

		$cause = $test['cause']['results'];
		$effect = $test['effect']['results'];

		self::showErrors($cause['errors'], $effect['errors'], $comparer, $displayer, $table);
		self::showException($cause['exception'], $effect['exception'], $comparer, $displayer, $table);
		self::showOutput($cause['output'], $effect['output'], $displayer, $table);
		self::showVariables($cause['variables'], $effect['variables'], $comparer, $displayer, $table);
		self::showGlobals($cause['globals'], $effect['globals'], $comparer, $displayer, $table);
		self::showConstants($cause['constants'], $effect['constants'], $comparer, $displayer, $table);
		self::showCalls($cause, $effect, $comparer, $displayer, $table);

		$resultsText = $table->getText();
		$filePosition = self::getFilePosition($file, $line);

		return self::indent($code, '   ') . "\n\n" .
			self::indent($resultsText, ' ') . "\n" .
			self::indent("See it: {$filePosition}", '   ');
	}

	private static function getCode($code)
	{
		return "// Test\n{$code}";
	}

	private static function showErrors(array $cause, array $effect, Comparer $comparer, Displayer $displayer, Table $table)
	{
		$causeIndexed = self::index($cause);
		$effectIndexed = self::index($effect);

		$indices = array_keys(array_merge($effectIndexed, $causeIndexed));

		foreach ($indices as $index) {
			if (self::isSame($index, $cause, $effect, $comparer)) {
				continue;
			}

			$causeOutput = self::getErrorText($causeIndexed, $index, $displayer);
			$effectOutput = self::getErrorText($effectIndexed, $index, $displayer);

			$table->addRow($causeOutput, $effectOutput);
		}
	}

	private static function index($input)
	{
		$output = array();

		foreach ($input as $key => $value) {
			$jsonValue = json_encode($value);
			$output[$jsonValue] = $value;
		}

		return $output;
	}

	private static function getErrorText(array $values, $key, Displayer $displayer)
	{
		if (!array_key_exists($key, $values)) {
			return null;
		}

		list($level, $message, $file, $line) = current($values[$key]);

		$nameText = self::getErrorLevelName($level);

		// TODO: here
		if (is_string($file)) {
			// TODO: replace the "getcwd" function call
			$file = rtrim(self::getRelativePath(getcwd(), $file), '/');
		}

		$fileText = self::getFilePosition($file, $line);
		$messageText = $displayer->display($message);

		return "{$nameText}: {$fileText}: {$messageText}";
	}

	private static function getErrorLevelName($level)
	{
		switch ($level)
		{
			case 1: return 'E_ERROR';
			case 2: return 'E_WARNING';
			case 4: return 'E_PARSE';
			case 8: return 'E_NOTICE';
			case 16: return 'E_CORE_ERROR';
			case 32: return 'E_CORE_WARNING';
			case 64: return 'E_COMPILE_ERROR';
			case 128: return 'E_COMPILE_WARNING';
			case 256: return 'E_USER_ERROR';
			case 512: return 'E_USER_WARNING';
			case 1024: return 'E_USER_NOTICE';
			case 2048: return 'E_STRICT';
			case 4096: return 'E_RECOVERABLE_ERROR';
			case 8192: return 'E_DEPRECATED';
			case 16384: return 'E_USER_DEPRECATED';
			case 32767: return 'E_ALL';
			default: return '';
		}
	}

	private static function showException($cause, $effect, Comparer $comparer, Displayer $displayer, Table $table)
	{
		if ($comparer->isSame($cause, $effect)) {
			return;
		}

		$causeOutput = self::getExceptionText($cause, $displayer);
		$effectOutput = self::getExceptionText($effect, $displayer);

		$table->addRow($causeOutput, $effectOutput);
	}

	private static function getExceptionText($object, Displayer $displayer)
	{
		if (!is_array($object)) {
			return null;
		}

		unset($object[Archivist::TYPE_OBJECT][2]['file']);
		unset($object[Archivist::TYPE_OBJECT][2]['line']);
		unset($object[Archivist::TYPE_OBJECT][2]['xdebug_message']);

		return 'throw ' . $displayer->display($object) . ';';
	}

	private static function showOutput($cause, $effect, Displayer $displayer, Table $table)
	{
		if ($cause === $effect) {
			return;
		}

		$causeOutput = self::getEchoText($cause, $displayer);
		$effectOutput = self::getEchoText($effect, $displayer);

		$table->addRow($causeOutput, $effectOutput);
	}

	private static function getEchoText($text, Displayer $displayer)
	{
		if ($text === '') {
			return null;
		}

		$value = $displayer->display($text);

		// TODO: word wrap elsewhere
		$delimiter = substr($value, 0, 1);
		$innerValue = substr($value, 1, -1);
		$value = $delimiter . wordwrap($innerValue, 72, "{$delimiter},\n     {$delimiter}", true) . $delimiter;

		return "echo {$value};";
	}

	private static function showVariables(array $cause, array $effect, Comparer $comparer, Displayer $displayer, Table $table)
	{
		$names = array_keys(array_merge($cause, $effect));
		sort($names, SORT_NATURAL);

		foreach ($names as $name) {
			if (self::isSame($name, $cause, $effect, $comparer)) {
				continue;
			}

			$causeOutput = self::getVariableText($cause, $name, $displayer);
			$effectOutput = self::getVariableText($effect, $name, $displayer);

			$table->addRow($causeOutput, $effectOutput);
		}
	}

	private static function isSame($key, array $a, array $b, Comparer $comparer)
	{
		return array_key_exists($key, $a) &&
			array_key_exists($key, $b) &&
			$comparer->isSame($a[$key], $b[$key]);
	}

	private static function getVariableText(array $variables, $name, Displayer $displayer)
	{
		if (!array_key_exists($name, $variables)) {
			return null;
		}

		$value = $displayer->display($variables[$name]);

		return "\${$name} = {$value};";
	}

	private static function showGlobals(array $cause, array $effect, Comparer $comparer, Displayer $displayer, Table $table)
	{
		$names = array_keys(array_merge($cause, $effect));
		sort($names, SORT_NATURAL);

		foreach ($names as $name) {
			if (self::isSame($name, $cause, $effect, $comparer)) {
				continue;
			}

			$causeOutput = self::getGlobalText($cause, $name, $displayer);
			$effectOutput = self::getGlobalText($effect, $name, $displayer);

			$table->addRow($causeOutput, $effectOutput);
		}
	}

	private static function getGlobalText(array $variables, $name, Displayer $displayer)
	{
		if (!array_key_exists($name, $variables)) {
			return null;
		}

		$value = $displayer->display($variables[$name]);

		return "\$GLOBALS['{$name}'] = {$value};";
	}

	private static function showConstants(array $cause, array $effect, Comparer $comparer, Displayer $displayer, Table $table)
	{
		$names = array_keys(array_merge($cause, $effect));
		sort($names, SORT_NATURAL);

		foreach ($names as $name) {
			if (self::isSame($name, $cause, $effect, $comparer)) {
				continue;
			}

			$causeOutput = self::getConstantText($cause, $name, $displayer);
			$effectOutput = self::getConstantText($effect, $name, $displayer);

			$table->addRow($causeOutput, $effectOutput);
		}
	}

	private static function getConstantText(array $constants, $name, Displayer $displayer)
	{
		if (!array_key_exists($name, $constants)) {
			return null;
		}

		$value = $displayer->display($constants[$name]);

		return "define('{$name}', {$value});\n";
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

	private static function showCalls(array $cause, array $effect, Comparer $comparer, Displayer $displayer, Table $table)
	{
		$causeNames = self::getObjectNames($cause['variables']);
		$effectNames = self::getObjectNames($effect['variables']);

		for ($i = 0, $n = max(count($cause), count($effect)); $i < $n; ++$i) {
			if (self::isSame($i, $cause['calls'], $effect['calls'], $comparer)) {
				continue;
			}

			$causeOutput = self::getCallText($cause['calls'], $i, $causeNames, $displayer);
			$effectOutput = self::getCallText($effect['calls'], $i, $effectNames, $displayer);

			$table->addRow($causeOutput, $effectOutput);
		}
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

	private static function getCallText(array $calls, $key, array $names, Displayer $displayer)
	{
		if (!array_key_exists($key, $calls)) {
			return null;
		}

		list($callableArchive, $argumentsArchive, $resultArchive) = current($calls[$key]);
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

	private static function indent($text, $padding)
	{
		// TODO: check this:
		return $padding . str_replace("\n", "\n" . $padding, $text);
	}
}
