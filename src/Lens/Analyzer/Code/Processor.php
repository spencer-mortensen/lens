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

namespace _Lens\Lens\Analyzer\Code;

use _Lens\Lens\Analyzer\Code\Parser\NamespaceLexer;
use _Lens\Lens\Analyzer\Code\Parser\NamespaceParser;
use _Lens\Lens\Php\Lexer as PhpLexer;

class Processor
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
		$this->deflator->deflate($inflatedTokens, $deflatedTokens, $map);
		$namespaces = $this->lexer->lex($deflatedTokens);

		return $this->getDefinitions($namespaces, $inflatedTokens, $deflatedTokens, $map);
	}

	private function getDefinitions(array $namespaces, array $inflatedTokens, array $deflatedTokens, array $map)
	{
		$definitions = [];

		foreach ($namespaces as $iBegin => $iEnd) {
			$restrictedInflatedTokens = $this->restrictTokens($inflatedTokens, $map[$iBegin], $map[$iEnd]);
			$restrictedDeflatedTokens = $this->restrictTokens($deflatedTokens, $iBegin, $iEnd);
			$restrictedMap = $this->restrictMap($map, $iBegin, $iEnd);

			$this->getNamespace($restrictedInflatedTokens, $restrictedDeflatedTokens, $restrictedMap, $definitions);
		}

		return $definitions;
	}

	private function restrictTokens(array $tokens, $iBegin, $iEnd)
	{
		$length = $iEnd - $iBegin + 1;
		return array_slice($tokens, $iBegin, $length);
	}

	private function restrictMap(array $values, $keyBegin, $keyEnd)
	{
		$output = [];

		$valueBegin = $values[$keyBegin];

		foreach ($values as $key => $value) {
			if (($key < $keyBegin) || ($keyEnd < $key)) {
				continue;
			}

			$output[$key - $keyBegin] = $value - $valueBegin;
		}

		return $output;
	}

	private function getNamespace(array $inflatedTokens, array $deflatedTokens, array $map, array &$output)
	{
		$data = $this->parser->parse($deflatedTokens);
		$context = $data['context'];
		$definitions = $data['definitions'];

		// TODO: use an integer named constant (instead of 'class', 'function', etc.)
		$types = [
			'class' => 'classes',
			'function' => 'functions',
			'interface' => 'interfaces',
			'trait' => 'traits'
		];

		foreach ($types as $typeId => $typeKey) {
			foreach ($definitions[$typeKey] as $name => $range) {
				$fullName = $this->getFullName($context['namespace'], $name);
				$definitionTokens = $this->restrictTokens($inflatedTokens, $map[$range[0]], $map[$range[1]]);

				$output[$fullName] = [
					'type' => $typeId,
					'context' => $context,
					'tokens' => $definitionTokens
				];
			}
		}
	}

	private function getFullName($namespace, $name)
	{
		if ($namespace === null) {
			return $name;
		}

		return "{$namespace}\\{$name}";
	}
}
