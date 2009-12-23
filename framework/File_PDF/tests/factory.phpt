--TEST--
File_PDF: factory() test
--FILE--
<?php

require_once dirname(__FILE__) . '/../PDF.php';

/* Old signature. */
$pdf = &File_PDF::factory('L', 'pt', 'A3');
var_dump($pdf->_default_orientation);
var_dump($pdf->_scale);
var_dump($pdf->fwPt);
var_dump($pdf->fhPt);
$pdf = &File_PDF::factory('L', 'pt');
var_dump($pdf->_default_orientation);
var_dump($pdf->_scale);
var_dump($pdf->fwPt);
var_dump($pdf->fhPt);

/* New signature. */
$pdf = &File_PDF::factory(array('orientation' => 'L', 'unit' => 'pt', 'format' => 'A3'));
var_dump($pdf->_default_orientation);
var_dump($pdf->_scale);
var_dump($pdf->fwPt);
var_dump($pdf->fhPt);
$pdf = &File_PDF::factory();
var_dump($pdf->_default_orientation);
var_dump(abs($pdf->_scale - 2.8346456692913) < 0.000001);
var_dump($pdf->fwPt);
var_dump($pdf->fhPt);

/* Custom class. */
class MyPDF extends File_PDF {}
$pdf = &File_PDF::factory(array(), 'MyPDF');
var_dump(strtolower(get_class($pdf)));
var_dump($pdf->_default_orientation);
var_dump(abs($pdf->_scale - 2.8346456692913) < 0.000001);
var_dump($pdf->fwPt);
var_dump($pdf->fhPt);

?>
--EXPECT--
string(1) "L"
int(1)
float(841.89)
float(1190.55)
string(1) "L"
int(1)
float(595.28)
float(841.89)
string(1) "L"
int(1)
float(841.89)
float(1190.55)
string(1) "P"
bool(true)
float(595.28)
float(841.89)
string(5) "mypdf"
string(1) "P"
bool(true)
float(595.28)
float(841.89)
