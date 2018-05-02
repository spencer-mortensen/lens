<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function move_uploaded_file($path, $new_path)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
