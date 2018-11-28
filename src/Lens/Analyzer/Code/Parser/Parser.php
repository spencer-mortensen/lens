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

namespace _Lens\Lens\Analyzer\Code\Parser;

use _Lens\Lens\Php\Lexer as PhpLexer;
use _Lens\Lens\Php\Deflator;
use _Lens\Lens\Php\Namespacing;

class Parser
{
	/** @var PhpLexer */
	private $phpLexer;

	/** @var Deflator */
	private $deflator;

	/** @var NamespaceLexer */
	private $lexer;

	/** @var NamespaceParser */
	private $parser;

	public function __construct()
	{
		// TODO: share these dependencies with the Tests parser?
		$this->phpLexer = new PhpLexer();
		$this->deflator = new Deflator();
		$this->lexer = new NamespaceLexer();
		$this->parser = new NamespaceParser();
	}

	public function parse($php)
	{
		$inflatedTokens = $this->phpLexer->lex($php);
		list($deflatedTokens, $metaTokens) = $this->deflator->deflate($inflatedTokens);
		$namespacedTokens = $this->lexer->lex($deflatedTokens);

		$sections = [];

		foreach ($namespacedTokens as $namespacedToken) {
			echo "input: ", json_encode($namespacedToken['tokens']), "\n\n";
			$data = $this->parser->parse($namespacedToken['tokens']);
			echo "output: ", json_encode($data), "\n\n";

			$definitions = $this->getDefinitions($data['definitions'], $deflatedTokens, $metaTokens, $namespacedToken['position']);

			$sections[] = [
				'namespace' => $data['namespace'],
				'uses' => $data['uses'],
				'definitions' => $definitions
			];
		}

		return $sections;
	}

	private function getDefinitions(array $definitions, array $deflatedTokens, array $metaTokens, $position)
	{
		$functions = [];
		$classes = [];
		$interfaces = [];
		$traits = [];

		foreach ($definitions['functions'] as $name => $range) {
			$inflatedTokens = $this->inflate($deflatedTokens, $metaTokens, $range, $position);
			$php = $this->getPhp($inflatedTokens);
			$functions[$name] = $php;
		}

		foreach ($definitions['classes'] as $name => $range) {
			$inflatedTokens = $this->inflate($deflatedTokens, $metaTokens, $range, $position);
			$php = $this->getPhp($inflatedTokens);
			$classes[$name] = $php;
		}

		foreach ($definitions['interfaces'] as $name => $range) {
			$inflatedTokens = $this->inflate($deflatedTokens, $metaTokens, $range, $position);
			$php = $this->getPhp($inflatedTokens);
			$interfaces[$name] = $php;
		}

		foreach ($definitions['traits'] as $name => $range) {
			$inflatedTokens = $this->inflate($deflatedTokens, $metaTokens, $range, $position);
			$php = $this->getPhp($inflatedTokens);
			$traits[$name] = $php;
		}

		return [
			'functions' => $functions,
			'classes' => $classes,
			'interfaces' => $interfaces,
			'traits' => $traits
		];
	}

	private function inflate(array $deflatedTokens, array $metaTokens, array $range, $offset)
	{
		list($iBegin, $iEnd) = $range;
		$iBegin += $offset;
		$iEnd += $offset;

		return $this->deflator->inflate($deflatedTokens, $metaTokens, $iBegin, $iEnd);
	}

	private function getPhp(array $tokens)
	{
		ob_start();

		foreach ($tokens as $token) {
			echo current($token);
		}

		$php = ob_get_clean();

		// TODO: convert any unsafe function calls

		// TODO: trim a leading "<?php\n" token (if necessary)
		// TODO: trim left indentation (if any)
		return ltrim($php, "\n");
	}
}
