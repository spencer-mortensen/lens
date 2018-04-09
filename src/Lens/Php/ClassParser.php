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

namespace Lens_0_0_56\Lens\Php;

use Lens_0_0_56\SpencerMortensen\Parser\String\Parser;
use Lens_0_0_56\SpencerMortensen\Parser\String\Rules;

class ClassParser extends Parser
{
	/** @var string */
	private $input;

	public function __construct()
	{
		$grammar = <<<'EOS'
class: AND classLine openingBrace classBody
classLine: RE [^{]+
classBody: RE .*
openingBrace: STRING {
closingBrace: STRING }
string: OR singleQuotedString doubleQuotedString docString
singleQuotedString: RE '(?:\\\\|\\\'|[^'])*'
doubleQuotedString: RE "(?:\\\\|\\\"|[^"])*"
docString: RE <<<(['"]?)(\w+)\1\r?\n.*?\n\2;?(?:\r?\n|$)
comment: OR multiLineComment singleLineComment
multiLineComment: RE /\*.*?\*/
singleLineComment: RE (?://|#).*?(?:\n|$)
EOS;

		$rules = new Rules($this, $grammar);
		$rule = $rules->getRule('class');

		parent::__construct($rule);
	}

	public function parse($input)
	{
		$input = <<<'EOS'
class C extends P implements I, J
{
	/**
         * This is a multiline comment
	 */
	private static $x;

	// private $y = 'hey';

	/*
	public function __construct()
	{
		$x = new Parser();
	}
	*/

	public function f(integer $x, ...$strings)
	{
		$d = new D();
		$d->f();

		D::g();

		$string = <<<'EOF'
	public function g()
	{
		echo "g\n";
	}
EOF;

		$transform = 'strtoupper';

		foreach ($strings as &$string) {
			$string = $transform($string);
		}

		$date = new \DateTime();
		$time = \time();
	}

	abstract public function g();
}
EOS;

		$this->input = $input;

		try {
			$output = parent::parse($input);
			echo "output: {$output}\n";
		} catch (\Exception $exception) {
			$output = null;
			echo "Exception\n";
		}

		return $output;
	}

	public function getClass(array $matches)
	{
		return implode('', $matches);
	}

	public function getDocString(array $match)
	{
		return $match[0];
	}
}
