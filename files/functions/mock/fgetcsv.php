<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function fgetcsv($fp, $length = null, $delimiter = null, $enclosure = null, $escape = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
