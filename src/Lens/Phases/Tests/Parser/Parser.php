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

namespace _Lens\Lens\Phases\Tests\Parser;

use _Lens\Lens\Php\Deflator;
use _Lens\Lens\Php\Lexer as PhpLexer;

class Parser
{
	/** @var PhpLexer */
	private $phpLexer;

	/** @var FileLexer */
	private $fileLexer;

	/** @var FileParser */
	private $fileParser;

	/** @var PreambleParser */
	private $preambleParser;

	/** @var MocksParser */
	private $mocksParser;

	/** @var EffectsLexer */
	private $effectsLexer;

	/** @var EffectsParser */
	private $effectsParser;

	public function __construct()
	{
		$deflator = new Deflator();
		$fileTokensParser = new FileTokensParser();
		$preambleTokensParser = new PreambleTokensParser();
		$mocksTokensParser = new MocksTokensParser();
		$effectsTokensParser = new EffectsTokensParser();

		$this->phpLexer = new PhpLexer();
		$this->fileLexer = new FileLexer($deflator);
		$this->fileParser = new FileParser($fileTokensParser);
		$this->preambleParser = new PreambleParser($deflator, $preambleTokensParser);
		$this->mocksParser = new MocksParser($deflator, $mocksTokensParser);
		$this->effectsLexer = new EffectsLexer($this->phpLexer);
		$this->effectsParser = new EffectsParser($deflator, $effectsTokensParser);
	}

	public function parse($php)
	{
		$phpTokens = $this->phpLexer->lex($php);
		$lensTokens = $this->fileLexer->lex($phpTokens);
		$sections = $this->fileParser->parse($lensTokens);

		return [
			'preamble' => $this->getPreamble($sections['preamble']),
			'mocks' => $this->getMocks($sections['mocks']),
			'tests' => $this->getTests($sections['tests'])
		];
	}

	private function getPreamble(array $preamble)
	{
		return $this->preambleParser->parse($preamble['tokens']);
	}

	private function getMocks($mocks)
	{
		if ($mocks === null) {
			return [];
		}

		return $this->mocksParser->parse($mocks['tokens'], $mocks['origin']);
	}

	private function getTests(array $tests)
	{
		$output = [];

		foreach ($tests as $line => $test) {
			$output[$line] = $this->getTest($test);
		}

		return $output;
	}

	private function getTest(array $test)
	{
		return [
			'subject' => $this->getSubject($test['subject']),
			'cases' => $this->getCases($test['cases'])
		];
	}

	private function getSubject(array $subject)
	{
		return self::getPhp($subject['tokens']);
	}

	private function getCases(array $cases)
	{
		$output = [];

		foreach ($cases as $line => $case) {
			$output[$line] = $this->getCase($case);
		}

		return $output;
	}

	private function getCase(array $case)
	{
		return [
			'cause' => $this->getCause($case['cause']),
			'effect' => $this->getEffect($case['effect'])
		];
	}

	private function getCause($cause)
	{
		if ($cause === null) {
			return null;
		}

		return self::getPhp($cause['tokens']);
	}

	private function getEffect($effect)
	{
		$tokens = $this->effectsLexer->lex($effect['tokens'], $effect['origin']);
		return $this->effectsParser->parse($tokens);
	}

	// TODO: this is duplicated elsewhere:
	private static function getPhp(array $tokens)
	{
		ob_start();

		foreach ($tokens as $token) {
			$type = key($token);

			if ($type === PhpLexer::COMMENT_) {
				continue;
			}

			$value = $token[$type];
			echo $value;
		}

		$php = ob_get_clean();

		return trim($php);
	}
}
