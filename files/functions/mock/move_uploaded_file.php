<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function move_uploaded_file($path, $new_path)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
