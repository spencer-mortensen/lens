<?php

namespace _Lens\SpencerMortensen\Parser\Input;

interface Input
{
	public function read($type, &$output = null);

	public function getPosition();

	public function setPosition($state);
}
