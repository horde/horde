<?php
/**
 * Barcode generator.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$vars = Horde_Variables::getDefaultVariables();

// Get text, uppercase, add start/stop characters.
$text = '*' . Horde_String::upper($vars->get('barcode', 'test'), true, 'UTF-8') . '*';
$textlen = strlen($text);

$height = $vars->get('h', 40);
$thinwidth = $vars->get('w', 2);
$thickwidth = $thinwidth * 3;
$width = $textlen * (7 * $thinwidth + 3 * $thickwidth) - $thinwidth;

$codingmap = array(
    '0' => '000110100',
    '1' => '100100001',
    '2' => '001100001',
    '3' => '101100000',
    '4' => '000110001',
    '5' => '100110000',
    '6' => '001110000',
    '7' => '000100101',
    '8' => '100100100',
    '9' => '001100100',
    'A' => '100001001',
    'B' => '001001001',
    'C' => '101001000',
    'D' => '000011001',
    'E' => '100011000',
    'F' => '001011000',
    'G' => '000001101',
    'H' => '100001100',
    'I' => '001001100',
    'J' => '000011100',
    'K' => '100000011',
    'L' => '001000011',
    'M' => '101000010',
    'N' => '000010011',
    'O' => '100010010',
    'P' => '001010010',
    'Q' => '000000111',
    'R' => '100000110',
    'S' => '001000110',
    'T' => '000010110',
    'U' => '110000001',
    'V' => '011000001',
    'W' => '111000000',
    'X' => '010010001',
    'Y' => '110010000',
    'Z' => '011010000',
    ' ' => '011000100',
    '$' => '010101000',
    '%' => '000101010',
    '*' => '010010100',
    '+' => '010001010',
    '-' => '010000101',
    '.' => '110000100',
    '/' => '010100010'
);

$image = new Horde_Image_Png(array(
    'background' => 'white',
    'context' => array(
        'tmpdir' => Horde::getTempDir()
    ),
    'height' => $height,
    'width' => $width));

$xpos = 0;
for ($idx = 0; $idx < $textlen; ++$idx) {
    $char = substr($text, $idx, 1);

    // Make unknown chars a '-'.
    if (!isset($codingmap[$char])) {
        $char = '-';
    }

    for ($bar = 0; $bar <= 8; $bar++) {
        $elementwidth = $codingmap[$char]{$bar} ? $thickwidth : $thinwidth;
        if (($bar + 1) % 2) {
            $image->rectangle($xpos, 0, $elementwidth - 1, $height, 'black', 'black');
        }
        $xpos += $elementwidth;
    }
    $xpos += $thinwidth;
}

header('Pragma: public');
$image->display();
