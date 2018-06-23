<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fputcsv($fp, $fields, $delimiter = null, $enclosure = null, $escape_char = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
