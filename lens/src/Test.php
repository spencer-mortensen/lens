<?php

namespace Example;

use Lens\Archivist\Archivist;

function send(array $state, array $script = [], array $coverage = null)
{
	$archivist = new Archivist();
	$archivedState = $archivist->archive($state);

	echo serialize(array($archivedState, $script, $coverage));
}
