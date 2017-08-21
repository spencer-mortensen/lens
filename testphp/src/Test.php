<?php

namespace Example;

use TestPhp\Archivist\Archivist;

function send(array $state, array $script = array(), array $coverage = null)
{
	$archivist = new Archivist();
	$archivedState = $archivist->archive($state);

	echo serialize(array($archivedState, $script, $coverage));
}