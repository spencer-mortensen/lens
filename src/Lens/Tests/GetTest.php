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

namespace _Lens\Lens\Tests;

use _Lens\Lens\Php\CallParser;
use _Lens\Lens\Php\Code;
use _Lens\Lens\Php\Namespacing;
use _Lens\SpencerMortensen\Parser\ParserException;

class GetTest
{
	/** @var Namespacing */
	private $namespacing;

	/** @var array */
	private $mockFunctions;

	/** @var CallParser */
	private $parser;

	/** @var string|null */
	private $contextPhp;

	/** @var string|null */
	private $test;

	/** @var string|null */
	private $cause;

	/** @var string|null */
	private $effect;

	/** @var array */
	private $script;

	public function __construct(Namespacing $namespacing, array $mockFunctions)
	{
		$this->namespacing = $namespacing;
		$this->mockFunctions = $mockFunctions;
		$this->parser = new CallParser();
	}

	public function setContext($namespace, array $uses)
	{
		$this->namespacing->setContext($namespace, $uses);
		$this->contextPhp = Code::getContextPhp($namespace, $uses);
	}

	public function setTest($test)
	{
		// TODO: check for any mock instantiations or mock method calls: throw exception if necessary
		$this->test = $test;
	}

	public function setCase($cause, $effect)
	{
		$this->script = [];
		$this->cause = $this->extractScript($cause);
		$this->effect = $this->extractScript($effect);
	}

	private function extractScript($php)
	{
		try {
			$tokens = $this->parser->parse('functionBody', $php);
		} catch (ParserException $exception) {
			$tokens = [];
		}

		// See: Sanitizer.php

/*
$object = new \Namespace\Class();
$variable = $object->method(); // return new \Namespace\Class();
$variable = \Namespace\Class::method($x, $y); // $x = new Class(); $y = new Class(); return 'blue';
\Namespace\Class::method(); // throw new Exception();
\Namespace\function();
*/

/*
$input = "new DateTime();"
$output = "new \Lens\DateTime();"
$script = ['Lens\DateTime', '__construct', null];

$input = "$dateTime = new DateTime();"
$output = "$dateTime = new \Lens\DateTime();"
$script = ['Lens|DateTime', '__construct', null];

$input = "$date->format('Y-m-d'); // return '2018-07-10';"
$output = "$date->format('Y-m-d');"
$script = [ <$date> , 'format', "return '2018-07-10';"]

$input = "date('Y-m-d'); // return '2018-07-10';"
$output = "\Lens\date('Y-m-d');"
$script = [null, 'Lens\date', "return '2018-07-10';"]

$input = "$btree = btree::open('my.tree'); // return new btree();"
$output = "$btree = \Lens\btree::open('my.tree');"
$script = [
	['Lens\btree', 'open', "return new \Lens\btree();"],
	['Lens\btree', '__construct', null]
]

$input = "list($a, $b) = C::f(); // return [new A(), new B()];"
$output = "list($a, $b) = \Lens\C::f();"
$script = [
	['Lens\C', 'f', "return [new \Lens\A(), new \Lens\B()];"],
	['Lens\A', '__construct', null],
	['Lens|B', '__construct', null]
]
*/
		echo "extracting script from ", json_encode($php), "\n";

		foreach ($tokens as $token) {
			echo "token: ", json_encode($token), "\n";
		}

		echo "\n";
		return $php;
	}

	public function getContextPhp()
	{
		return $this->contextPhp;
	}

	public function getTestPhp()
	{
		return $this->test;
	}

	public function getCausePhp()
	{
		return $this->cause;
	}

	public function getEffectPhp()
	{
		return $this->effect;
	}

	public function getScript()
	{
		return $this->script;
	}
}
