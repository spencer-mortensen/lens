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

namespace Lens_0_0_56\Lens;

use Lens_0_0_56\Lens\Php\Code;

class CaseText
{
	/** @var int */
	private $maximumLineLength;

	/** @var string */
	private $testsFile;

	/** @var int */
	private $caseLine;

	/** @var string|null */
	private $requirePhp;

	/** @var string|null */
	private $headerPhp;

	/** @var string */
	private $testPhp;

	/** @var string|null */
	private $inputPhp;

	/** @var array */
	private $issues;

	public function __construct($maximumLineLength = 96)
	{
		$this->maximumLineLength = $maximumLineLength;
	}

	public function setAutoload($autoload)
	{
		$this->requirePhp = $this->getRequireAutoloadPhp($autoload);
	}

	private function getRequireAutoloadPhp($autoload)
	{
		if ($autoload === null) {
			return null;
		}

		$valuePhp = Code::getValuePhp($autoload);
		return Code::getRequirePhp($valuePhp);
	}

	public function setSuite($testsFile, $namespace, array $uses)
	{
		$this->testsFile = $testsFile;
		$this->headerPhp = $this->getHeaderPhp($namespace, $uses, $this->requirePhp);
	}

	private function getHeaderPhp($namespace, array $uses, $requirePhp)
	{
		$contextPhp = Code::getContextPhp($namespace, $uses);
		return Code::combine($contextPhp, $requirePhp);
	}

	public function setTest($testPhp)
	{
		$this->testPhp = $testPhp;
	}

	public function setCase($caseLine, $inputPhp, array $issues)
	{
		$this->caseLine = $caseLine;
		$this->inputPhp = $inputPhp;
		$this->issues = $issues;
	}

	public function getText()
	{
		$headerText = "=== {$this->testsFile}:{$this->caseLine} ===";
		$headerText = str_pad($headerText, $this->maximumLineLength - 3, '=', STR_PAD_RIGHT);
		$headerText = "{$headerText}\n{$this->headerPhp}";
		$headerText = Paragraph::wrap($headerText, $this->maximumLineLength - 3);
		$headerText = Paragraph::indent($headerText, '   ');

		$inputText = $this->getSectionText('// Input', $this->inputPhp);
		$inputText = Paragraph::wrap($inputText, $this->maximumLineLength - 3);
		$inputText = Paragraph::indent($inputText, '   ');

		$testText = $this->getSectionText('// Test', $this->testPhp);
		$testText = Paragraph::wrap($testText, $this->maximumLineLength - 3);
		$testText = Paragraph::indent($testText, '   ');

		$outputText = $this->getOutputText($this->issues);
		$outputText = $this->getSectionText('   // Output', $outputText);

		$sections = array(
			$headerText,
			$inputText,
			$testText,
			$outputText
		);

		$sections = array_filter($sections, 'is_string');
		return implode("\n\n", $sections);
	}

	private function getOutputText(array $issues)
	{
		$lines = array();

		foreach ($issues as $issue) {
			if (is_string($issue)) {
				$lines[] = $this->getIssueLine(' ', $issue);
				continue;
			}

			if ($issue['expected'] !== null) {
				$lines[] = $this->getIssueLine('-', $issue['expected']);
			}

			if ($issue['actual'] !== null) {
				$lines[] = $this->getIssueLine('+', $issue['actual']);
			}
		}

		return implode("\n", $lines);
	}

	private function getIssueLine($label, $issue)
	{
		$text = Paragraph::wrap($issue, $this->maximumLineLength - 3);
		$text = Paragraph::indent($text, '   ');
		return substr_replace($text, $label, 1, 1);
	}

	private function getSectionText($label, $code)
	{
		if ($code === null) {
			return null;
		}

		return "{$label}\n{$code}";
	}
}
