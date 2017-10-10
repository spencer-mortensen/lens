<?php

namespace Lens;


// Test
$base = new Base($basePath);
$isChildPath = $base->isChildPath($absolutePath);


// Input
$basePath = null;
$absolutePath = '/bin/bash';

// Output
$isChildPath = true;


// Input
$basePath = '/tmp';
$absolutePath = '/bin/bash';

// Output
$isChildPath = false;


// Input
$basePath = '/tmp';
$absolutePath = '/tmp';

// Output
$isChildPath = true;


// Input
$basePath = '/tmp';
$absolutePath = '/tmp/directory';

// Output
$isChildPath = true;


// Input
$basePath = '/tmp';
$absolutePath = '/tmp-directory';

// Output
$isChildPath = false;


// Test
$base = new Base($basePath);
$relativePath = $base->getRelativePath($absolutePath);


// Input
$basePath = null;
$absolutePath = '/bin/bash';

// Output
$relativePath = '/bin/bash';


// Input
$basePath = '/tmp';
$absolutePath = '/bin/bash';

// Output
$relativePath = '../bin/bash';


// Input
$basePath = '/tmp';
$absolutePath = '/tmp';

// Output
$relativePath = '';


// Input
$basePath = '/tmp';
$absolutePath = '/tmp/directory';

// Output
$relativePath = 'directory';


// Input
$basePath = '/tmp';
$absolutePath = '/tmp-directory';

// Output
$relativePath = '../tmp-directory';


// Input
$basePath = '/tmp';
$absolutePath = '/tmp/directory/subdirectory';

// Output
$relativePath = 'directory/subdirectory';


// Test
$base = new Base($basePath);
$absolutePath = $base->getAbsolutePath($relativePath);


// Input
$basePath = null;
$relativePath = '/bin/bash';

// Output
$absolutePath = '/bin/bash';


// Input
$basePath = '/tmp';
$relativePath = '../bin/bash';

// Output
$absolutePath = '/bin/bash';


// Input
$basePath = '/tmp';
$relativePath = '';

// Output
$absolutePath = '/tmp';


// Input
$basePath = '/tmp';
$relativePath = 'directory';

// Output
$absolutePath = '/tmp/directory';


// Input
$basePath = '/tmp';
$relativePath = '../tmp-directory';

// Output
$absolutePath = '/tmp-directory';


// Input
$basePath = '/tmp';
$relativePath = 'directory/subdirectory';

// Output
$absolutePath = '/tmp/directory/subdirectory';
