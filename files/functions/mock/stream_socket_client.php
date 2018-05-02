<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_socket_client($remoteaddress, &$errcode = null, &$errstring = null, $timeout = null, $flags = null, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
