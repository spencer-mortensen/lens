<?php

namespace Lens;

use Lens\Archivist\Archivist;
use Lens\Archivist\Archives\ObjectArchive;
use Lens\Archivist\Archives\ResourceArchive;

require LENS . '/bootstrap.php';


// Test
$archivist = new Archivist();
$output = $archivist->archive($input);

// Input
$input = 12;

// Output
$output = 12;

