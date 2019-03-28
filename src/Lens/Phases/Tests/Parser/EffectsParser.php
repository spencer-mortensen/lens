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

use _Lens\Lens\Exceptions\ParsingException;
use _Lens\Lens\Php\Deflator;
use _Lens\SpencerMortensen\Parser\Input\TokenInput;

class EffectsParser
{
	const TYPE_ARRAY = 1;
	const TYPE_ASSIGN = 2;
	const TYPE_CALL = 3;
	const TYPE_CONSTANT = 4;
	const TYPE_ECHO = 5;
	const TYPE_GLOBAL = 6;
	const TYPE_RETURN = 7;
	const TYPE_THROW = 8;
	const TYPE_VARIABLE = 9;

	/** @var Deflator */
	private $deflator;

	/** @var EffectsTokensParser */
	private $parser;

	public function __construct(Deflator $deflator, EffectsTokensParser $parser)
	{
		$this->deflator = $deflator;
		$this->parser = $parser;
	}

	public function parse(array $tokens)
	{
		$output = [];

		foreach ($tokens as $token) {
			$type = key($token);
			$value = $token[$type];

			$statements = $this->getStatements($value['tokens'], $value['origin']);

			if ($type === EffectsLexer::PHP) {
				$this->addPhpStatements($output, $statements);
			} elseif (!$this->addBodyStatements($output, $statements, $token)) {
				throw $this->getBodyStatementException($token);
			}
		}

		return $output;
	}

	private function addPhpStatements(array &$output, array $statements)
	{
		$output = array_merge($output, $statements);
	}

	private function addBodyStatements(array &$phpStatements, array $bodyStatements, array $token)
	{
		$priorPosition = count($phpStatements) - 1;

		if ($priorPosition < 0) {
			return false;
		}

		$priorStatement = &$phpStatements[$priorPosition];

		return $this->addCallBodyStatements($priorStatement, $bodyStatements)
			|| $this->addAssignBodyStatements($priorStatement, $bodyStatements);
	}

	private function getBodyStatementException(array $token)
	{
		$value = current($token);

		$expected = 'statement';
		$actual = '//' . $this->getPhp($value['tokens']);
		$coordinates = self::add($value['origin'], [-2, 0]);

		return new ParsingException($coordinates, $expected, $actual);
	}

	private function getPhp(array $tokens)
	{
		ob_start();

		foreach ($tokens as $token) {
			echo current($token);
		}

		return ob_get_clean();
	}

	private function addCallBodyStatements(&$callStatement, array $bodyStatements)
	{
		if (!is_array($callStatement)) {
			return false;
		}

		$type = key($callStatement);

		if ($type !== self::TYPE_CALL) {
			return false;
		}

		$callStatement[$type][] = $bodyStatements;
		return true;
	}

	private function addAssignBodyStatements(array &$assignStatement, array $bodyStatements)
	{
		if (!is_array($assignStatement)) {
			return false;
		}

		$type = key($assignStatement);

		if ($type !== self::TYPE_ASSIGN) {
			return false;
		}

		$callStatement = &$assignStatement[$type][1];
		return $this->addCallBodyStatements($callStatement, $bodyStatements);
	}

	private function getStatements(array $inflatedTokens, array $origin)
	{
		list($deflatedTokens, $metaTokens) = $this->deflator->deflate($inflatedTokens);

		if (count($deflatedTokens) === 0) {
			return [];
		}

		$input = new TokenInput($deflatedTokens);

		if (
			$this->parser->parse($input) &&
			($this->parser->getPosition() === count($deflatedTokens))
		) {
			return $this->parser->getOutput();
		}

		throw $this->newException($inflatedTokens, $metaTokens, $origin);
	}

	// TODO: this is duplicated elsewhere:
	private function newException(array $inflatedTokens, array $metaTokens, array $origin)
	{
		$deflatedPosition = $this->parser->getPosition();
		$inflatedPosition = $this->deflator->getInflatedPosition($inflatedTokens, $metaTokens, $deflatedPosition);

		$offset = $this->getCoordinates($inflatedTokens, $inflatedPosition);
		$coordinates = self::add($origin, $offset);

		$expectation = $this->parser->getExpectation();
		$expected = $this->getExpected($expectation);

		$actual = $this->getActual($inflatedTokens, $inflatedPosition);

		return new ParsingException($coordinates, $expected, $actual);
	}

	// TODO: this is duplicated elsewhere
	private function getCoordinates(array $tokens, $position)
	{
		$x = 0;
		$y = 0;

		for ($i = 0; $i < $position; ++$i) {
			$token = $tokens[$i];
			$value = current($token);

			$this->updatePosition($value, $x, $y);
		}

		return [$x, $y];
	}

	// TODO: this is duplicated elsewhere
	private function updatePosition($text, &$x, &$y)
	{
		$dy = substr_count($text, "\n");
		$y += $dy;

		if ($dy === 0) {
			$x += strlen($text);
		} else {
			$x = strlen($text) - (strrpos($text, "\n") + 1);
		}
	}

	private function getExpected($expectation)
	{
		// TODO:
		// throw new ErrorException("Undefined expectation ({$expectation})", null, E_USER_ERROR, __FILE__, __LINE__);
		return $expectation;
		/*
		switch ($expectation)
		{
			case 'mocks':
				return '// Mocks';

			case 'subject':
				return '// Test';

			case 'cause':
				return '// Cause';

			case 'effect':
				return '// Effect';

			default:
				// TODO: throw exception
				return null;
		}
		*/
	}

	private function getActual(array $tokens, $position)
	{
		$tail = '';

		for ($i = $position, $n = count($tokens); $i < $n; ++$i) {
			$token = $tokens[$i];
			$value = current($token);

			$iNewline = strpos($value, "\n");

			if (is_int($iNewline)) {
				$tail .= substr($value, 0, $iNewline);
				return $tail;
			} else {
				$tail .= $value;
			}
		}

		return $tail;
	}

	private static function add(array $a, array $b)
	{
		if ($b[1] === 0) {
			$x = $a[0] + $b[0];
			$y = $a[1];
		} else {
			$x = $b[0];
			$y = $a[1] + $b[1];
		}

		return [$x, $y];
	}
}
