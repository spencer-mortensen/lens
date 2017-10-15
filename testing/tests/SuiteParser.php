<?php

namespace Lens;

use SpencerMortensen\Parser\ParserException;


// Test
$parser = new SuiteParser();
$output = $parser->parse($input);


// Input
$input = '';

// Output
throw new ParserException('phpTag', 0);


// Input
$input = "<?php";

// Output
throw new ParserException('subjectLabel', 5);


// Input
$input = "<?php\n// test";

// Output
throw new ParserException('subjectLabel', 13);


// Input
$input = "<?php\n//Test";

// Output
throw new ParserException('subjectLabel', 12);


// Input
$input = "<?php\n// Test";

// Output
throw new ParserException('outputLabel', 13);


// Input
$input = "<?php\n// Test\n\$x = 1 + 1;\n// Output\n\$x = 2;";

// Output
$output = json_decode('{"fixture": null, "tests": [{"subject": "$x = 1 + 1;", "cases": [{"input": null, "output": "$x = 2;"}]}]}', true);


// Input
$input = "<?php\n\n/**\n * Copyright\n */\n\n// Test\n\$x = 1 + 1;\n\n/*\n// Input\n\$x = 1 + 2;\n\n// Output\n\$x = 3;\n*/\n\n/*\n// Input\n\$x = 1 + 3;\n\n// Output\n\$x = 4;\n*/\n\n// Output\n\$x = 2;\n";

// Output
$output = json_decode('{"fixture": null, "tests": [{"subject": "$x = 1 + 1;", "cases": [{"input": null, "output": "$x = 2;"}]}]}', true);


// Input
$input = "<?php\n\n// Test\n\$output = \$input;\n\n// Input\n\$input = 1;\n\n// Output\n\$output = 1;";

// Output
$output = json_decode('{"fixture": null, "tests": [{"subject": "$output = $input;", "cases": [{"input": "$input = 1;", "output": "$output = 1;"}]}]}', true);


// Input
$input = "<?php\n\nnamespace Example;\n\n\$terminal = new Terminal(); // Mock\n\$speller = new Speller($terminal);\n\n// Test\n\$speller->start();\n\n// Output\n\$terminal->write('Type a word:');\n\$terminal->read(); // return 'perfectly';\n\$terminal->write('The word \"perfectly\" is spelled: P-E-R-F-E-C-T-L-Y!');";

// Output
$output = json_decode('{"fixture": "namespace Example;\n\n$terminal = new Terminal(); // Mock\n$speller = new Speller();", "tests": [{"subject": "$speller->start();", "cases": [{"input": null, "output": "$terminal->write(\'Type a word:\');\n$terminal->read(); // return \'perfectly\';\n$terminal->write(\'The word \\"perfectly\\" is spelled: P-E-R-F-E-C-T-L-Y!\');"}]}]}', true);


// Input
$input = "<?php\n\n/" . "**\n * Copyright\n *" . "/\n\nnamespace Example;\n\n/" . "* Description *" . "/\n\n\$math = new Math();\n\n// Test\n\$x = 1 + 1;\n// Output\n\$x = 2;";

// Output
$output = json_decode('{"fixture": "namespace Example;\n$math = new Math();", "tests": [{"subject": "$x = 1 + 1;", "cases": [{"input": null, "output": "$x = 2;"}]}]}', true);
