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

use _Lens\SpencerMortensen\Html5\Elements\Article;
use _Lens\SpencerMortensen\Html5\Elements\Body;
use _Lens\SpencerMortensen\Html5\Elements\Head;
use _Lens\SpencerMortensen\Html5\Elements\Header;
use _Lens\SpencerMortensen\Html5\Elements\Html;
use _Lens\SpencerMortensen\Html5\Elements\Link;
use _Lens\SpencerMortensen\Html5\Elements\Meta;
use _Lens\SpencerMortensen\Html5\Elements\Nav;
use _Lens\SpencerMortensen\Html5\Elements\Title;
use _Lens\SpencerMortensen\Html5\Document;
use _Lens\SpencerMortensen\Html5\Node;
use _Lens\SpencerMortensen\Html5\Text;

class Page implements Node
{
	/** @var Document */
	private $document;

	public function __construct(array $directoryComponents, $title, Node $navigation, Node $article)
	{
		$styleUrl = $this->getRelativeUrl($directoryComponents, ['.theme', 'style.css']);
		$fontsUrl = 'https://fonts.googleapis.com/css?family=Lora|Inconsolata';
		$faviconUrl = $this->getRelativeUrl($directoryComponents, ['favicon.ico']);

		$this->document = new Document(
			new Html(['lang' => 'en'],
				new Head(null, [
					new Meta(['http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8']),
					new Title(null, new Text($title)),
					new Link(['href' => $styleUrl, 'rel' => 'stylesheet', 'type' => 'text/css; charset=utf-8']),
					new Link(['href' => $fontsUrl, 'rel' => 'stylesheet', 'type' => 'text/css; charset=utf-8']),
					new Link(['href' => $faviconUrl, 'rel' => 'shortcut icon', 'type' => 'image/x-icon'])
				]),
				new Body(null, [
					new Header(null, new Nav(null, $navigation)),
					new Article(null, $article)
				])
			)
		);
	}

	private function getRelativeUrl(array $aComponents, array $bComponents)
	{
		$aCount = count($aComponents);
		$bCount = count($bComponents);

		for ($i = 0, $n = min($aCount, $bCount); ($i < $n) && ($aComponents[$i] === $bComponents[$i]); ++$i);

		$cComponents = array_merge(array_fill(0, $aCount - $i, '..'), array_slice($bComponents, $i));

		return implode('/', $cComponents);
	}

	public function __toString()
	{
		return (string)$this->document;
	}
}
