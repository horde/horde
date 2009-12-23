#!@php_bin@
<?php
/**
 * This script highlights various source files on the console.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  devtools
 * @author   Jan Schneider <jan@horde.org>
 */

if (!isset($argv[1])) {
    echo "Usage: highlight.php SOURCE_FILE [HIGHLIGHTER]\n";
    exit;
}

require_once 'Text/Highlighter.php';
require_once 'Text/Highlighter/Renderer/Console.php';

/* File to highlight. */
$file = $argv[1];

/* Optional highlighter. */
if (isset($argv[2])) {
    $type = $argv[2];
} else {
    /* Try autodetecting. */
    $map = array('cpp' => 'CPP',
                 'css' => 'CSS',
                 'diff' => 'DIFF', 'patch' => 'DIFF',
                 'dtd' => 'DTD',
                 'js' => 'JAVASCRIPT',
                 'pl' => 'PERL',
                 'php' => 'PHP',
                 'py' => 'PYTHON',
                 'sql' => 'SQL',
                 'xml' => 'XML');
    $ext = strtolower(substr($file, strrpos($file, '.') + 1));
    if (isset($map[$ext])) {
        $type = $map[$ext];
    } else {
        $type = 'PHP';
    }
}

$hl = Text_Highlighter::factory($type);
$hl->setRenderer(new Text_Highlighter_Renderer_Console());

echo $hl->highlight(file_get_contents($file));
