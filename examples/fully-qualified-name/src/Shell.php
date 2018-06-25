<?php

namespace Example;

class Shell
{
	public function run($command)
	{
		\shell_exec($command);
	}
}
