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

namespace _Lens\Lens\Reports\Coverage\Html5;

use _Lens\SpencerMortensen\Html5\Node;
use _Lens\SpencerMortensen\Html5\Text;
use _Lens\SpencerMortensen\Html5\Elements\Table;
use _Lens\SpencerMortensen\Html5\Elements\Td;
use _Lens\SpencerMortensen\Html5\Elements\Th;
use _Lens\SpencerMortensen\Html5\Elements\Tr;

class CodeCoverage implements Node
{
	/** @var Node */
	private $node;

	public function __construct(array $lines, array $coverage)
	{
		$this->node = $this->getTable($lines, $coverage);
	}

	private function getTable(array $lines, array $coverage)
	{
		$children = [];

		foreach ($lines as $i => $line) {
			$lineNumber = $i + 1;

			$attributes = $this->getTrAttributes($coverage, $i);

			$children[] = new Tr($attributes, [
				new Th(null, new Text($lineNumber)),
				new Td(null, new Text($line))
			]);
		}

		return new Table(null, $children);
	}

	private function getTrAttributes(array $coverage, $i)
	{
		if (isset($coverage[$i])) {
			return [
				'class' => $this->getClass($coverage[$i])
			];
		}

		return [];
	}

	private function getClass($isPass)
	{
		if ($isPass) {
			return 'pass';
		}

		return 'fail';
	}

	public function __toString()
	{
		return (string)$this->node;
	}
}
