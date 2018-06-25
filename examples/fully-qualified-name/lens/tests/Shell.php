<?php

namespace Example;

// Test
$shell = new Shell();
$shell->run('whoami');

// Effect
shell_exec('whoami'); // return 'user';

// Effect
shell_exec('whoami'); // return 'root';
