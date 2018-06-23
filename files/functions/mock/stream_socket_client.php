<?php

namespace Lens;

use _Lens\Lens\Tests\Agent;

function stream_socket_client($remoteaddress, &$errcode = null, &$errstring = null, $timeout = null, $flags = null, $context = null)
{
	return eval(Agent::call(null, __FUNCTION__, func_get_args()));
}
