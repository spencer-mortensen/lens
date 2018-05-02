<?php

namespace Lens;

use Lens_0_0_56\Lens\Evaluator\Agent;

function stream_socket_recvfrom($stream, $amount, $flags = null, &$remote_addr = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
