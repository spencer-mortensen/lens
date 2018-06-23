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

class IndexPage implements Node
{
	/** @var Node */
	private $page;

	public function __construct(array $directoryAtoms, array $baseAtoms, array $pageAtoms, array $links)
	{
		$title = $this->getTitle($directoryAtoms);

		$menu = new Breadcrumbs($baseAtoms, $pageAtoms);
		$index = new Index($links);
		$this->page = new Page($directoryAtoms, $title, $menu, $index);
	}

	private function getTitle(array $pageAtoms)
	{
		return end($pageAtoms);
	}

	public function __toString()
	{
		return (string)$this->page;
	}
}
