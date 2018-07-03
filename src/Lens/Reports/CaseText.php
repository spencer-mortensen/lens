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

namespace _Lens\Lens\Reports;

use _Lens\Lens\Paragraph;
use _Lens\Lens\Php\Code;
use _Lens\SpencerMortensen\Filesystem\Path;

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
	private $causePhp;

	/** @var array */
	private $issues;

	public function __construct($maximumLineLength = 96)
	{
		$this->maximumLineLength = $maximumLineLength;
	}

	public function setAutoload(Path $autoload = null)
	{
		$this->requirePhp = $this->getRequireAutoloadPhp($autoload);
	}

	private function getRequireAutoloadPhp(Path $autoload = null)
	{
		if ($autoload === null) {
			return null;
		}

		$valuePhp = Code::getValuePhp((string)$autoload);
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

	public function setCase($caseLine, $causePhp, array $issues)
	{
		$this->caseLine = $caseLine;
		$this->causePhp = $causePhp;
		$this->issues = $issues;
	}

	public function getText()
	{
		$sections = [
			$this->getHeaderSectionText(),
			$this->getCauseSectionText(),
			$this->getTestSectionText(),
			$this->getEffectSectionText()
		];

		$sections = array_filter($sections, 'is_string');
		return implode("\n\n", $sections);
	}

	private function getHeaderSectionText()
	{
		$text = "=== {$this->testsFile}:{$this->caseLine} ===";
		$text = str_pad($text, $this->maximumLineLength - 3, '=', STR_PAD_RIGHT);
		$text = "{$text}\n{$this->headerPhp}";
		$text = Paragraph::wrap($text, $this->maximumLineLength - 3);
		return Paragraph::indent($text, '   ');
	}

	private function getCauseSectionText()
	{
		if ($this->causePhp === null) {
			return null;
		}

		$text = $this->getSectionText('// Cause', $this->causePhp);
		$text = Paragraph::wrap($text, $this->maximumLineLength - 3);
		return Paragraph::indent($text, '   ');
	}

	private function getTestSectionText()
	{
		$text = $this->getSectionText('// Test', $this->testPhp);
		$text = Paragraph::wrap($text, $this->maximumLineLength - 3);
		return Paragraph::indent($text, '   ');
	}

	private function getEffectSectionText()
	{
		$text = $this->getEffectText($this->issues);
		return $this->getSectionText('   // Effect', $text);
	}

	private function getEffectText(array $issues)
	{
		$lines = [];

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
