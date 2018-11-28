<?php

namespace _Lens\SpencerMortensen\Parser;

class Paragraph
{
	public static function indent($text, $padding)
	{
		return self::replace('([^\\v]+)', $padding . '$1', $text);
	}

	private static function replace($expression, $replacement, $input)
	{
		$delimiter = "\x03";
		$flags = 'XDs';

		$pattern = $delimiter . $expression . $delimiter . $flags;

		return preg_replace($pattern, $replacement, $input);
	}	
}
