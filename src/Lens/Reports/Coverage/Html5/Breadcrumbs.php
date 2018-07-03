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
use _Lens\SpencerMortensen\Html5\Elements\A;
use _Lens\SpencerMortensen\Html5\Elements\Li;
use _Lens\SpencerMortensen\Html5\Elements\Ul;
use _Lens\SpencerMortensen\Html5\Text;

class Breadcrumbs implements Node
{
	/** @var array */
	private $baseComponents;

	/** @var array */
	private $pageComponents;

	public function __construct(array $baseComponents, array $pageComponents)
	{
		$this->baseComponents = $baseComponents;
		$this->pageComponents = $pageComponents;
	}

	public function __toString()
	{
		$ul = $this->getMenuLinks($this->baseComponents, $this->pageComponents);
		return (string)$ul;
	}

	private function getMenuLinks(array $baseComponents, array $pageComponents)
	{
		$children = [];

		$mutualPrefixLength = count($baseComponents);

		for ($i = 1, $n = count($pageComponents); $i <= $n; ++$i) {
			$name = $pageComponents[$i - 1];
			$link = $this->getLink($name, $mutualPrefixLength - $i, $n - $i);

			$children[] = new Li(null, $link);
		}

		return new Ul(null, $children);
	}

	private function getLink($name, $a, $depth)
	{
		$text = new Text($name);

		if (0 < $a) {
			return $text;
		}

		$attributes = [];
		$children = [$text];

		if (0 < $depth) {
			$attributes['href'] = $this->getLinkUrl($depth);
		}

		return new A($attributes, $children);
	}

	private function getLinkUrl($depth)
	{
		$names = array_fill(0, $depth, '..');
		$names[] = 'index.html';

		return implode('/', $names);
	}
}
