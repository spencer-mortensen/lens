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

use _Lens\SpencerMortensen\Html5\Elements\A;
use _Lens\SpencerMortensen\Html5\Elements\Li;
use _Lens\SpencerMortensen\Html5\Elements\Ul;
use _Lens\SpencerMortensen\Html5\Node;
use _Lens\SpencerMortensen\Html5\Text;

class Index implements Node
{
	/** @var Node */
	private $node;

	public function __construct(array $links)
	{
		$this->node = $this->getUl($links);
	}

	private function getUl(array $links)
	{
		usort($links, [$this, 'compareLinks']);

		$children = [];

		foreach ($links as $link) {
			list($name, $type, $url, $tested, $testable) = $link;

			$nameText = $this->getNameText($name, $type);

			$children[] = new Li(null, [
				new A(['href' => $url], new Text($nameText)),
				new Percent($tested, $testable)
			]);
		}

		return new Ul(null, $children);
	}

	private function compareLinks(array $a, array $b)
	{
		$typeComparison = $this->getLinkTypeValue($a) - $this->getLinkTypeValue($b);

		if ($typeComparison !== 0) {
			return $typeComparison;
		}

		return strcasecmp($this->getLinkName($a), $this->getLinkName($b));
	}

	private function getLinkTypeValue(array $link)
	{
		$type = $link[1];

		if ($type === 'name') {
			return 0;
		}

		return 1;
	}

	private function getLinkName(array $link)
	{
		return $link[0];
	}

	private function getNameText($name, $type)
	{
		if ($type === 'name') {
			return "{$name}/";
		}

		return $name;
	}

	public function __toString()
	{
		return (string)$this->node;
	}
}
