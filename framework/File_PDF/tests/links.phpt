--TEST--
File_PDF: Link tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../PDF.php';

// Set up the pdf object.
$pdf = &File_PDF::factory(array('orientation' => 'P', 'format' => 'A4'));
// Start the document.
$pdf->open();
// Deactivate compression.
$pdf->setCompression(false);
// Start a page.
$pdf->addPage();
// Set font to Helvetica 12 pt.
$pdf->setFont('Helvetica', 'U', 12);
// Write linked text.
$pdf->write(15, 'Horde', 'http://www.horde.org');
// Add line break.
$pdf->write(15, "\n");
// Write linked text.
$link = $pdf->addLink();
$pdf->write(15, 'here', $link);
// Start next page.
$pdf->addPage();
// Add link anchor.
$pdf->setLink($link);
// Create linked image.
$pdf->image(dirname(__FILE__) . '/horde-power1.png', 15, 15, 0, 0, '', 'http://pear.horde.org/');
// Print the generated file.
echo $pdf->getOutput();

?>
--EXPECTF--
%PDF-1.3
3 0 obj
<</Type /Page
/Parent 1 0 R
/Resources 2 0 R
/Annots [<</Type /Annot /Subtype /Link /Rect [31.19 798.28 63.86 786.28] /Border [0 0 0] /A <</S /URI /URI (http://www.horde.org)>>>><</Type /Annot /Subtype /Link /Rect [31.19 755.76 55.20 743.76] /Border [0 0 0] /Dest [5 0 R /XYZ 0 841.89 null]>>]
/Contents 4 0 R>>
endobj
4 0 obj
<</Length 155>>
stream
2 J
0.57 w
BT /F1 12.00 Tf ET
BT 31.19 788.68 Td (Horde) Tj ET 31.19 787.48 32.68 -0.60 re f
BT 31.19 746.16 Td (here) Tj ET 31.19 744.96 24.01 -0.60 re f

endstream
endobj
5 0 obj
<</Type /Page
/Parent 1 0 R
/Resources 2 0 R
/Annots [<</Type /Annot /Subtype /Link /Rect [42.52 799.37 126.52 768.37] /Border [0 0 0] /A <</S /URI /URI (http://pear.horde.org/)>>>>]
/Contents 6 0 R>>
endobj
6 0 obj
<</Length 73>>
stream
2 J
0.57 w
BT /F1 12.00 Tf ET
q 84.00 0 0 31.00 42.52 768.37 cm /I1 Do Q

endstream
endobj
1 0 obj
<</Type /Pages
/Kids [3 0 R 5 0 R ]
/Count 2
/MediaBox [0 0 595.28 841.89]
>>
endobj
7 0 obj
<</Type /Font
/BaseFont /Helvetica
/Subtype /Type1
/Encoding /WinAnsiEncoding
>>
endobj
8 0 obj
<</Type /XObject
/Subtype /Image
/Width 84
/Height 31
/ColorSpace /DeviceRGB
/BitsPerComponent 8
/Filter /FlateDecode
/DecodeParms <</Predictor 15 /Colors 3 /BitsPerComponent 8 /Columns 84>>
/Length 2202>>
stream
%s
%s
%s
%s
%s
%s
%s
%s
%s
endstream
endobj
2 0 obj
<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]
/Font <<
/F1 7 0 R
>>
/XObject <<
/I1 8 0 R
>>
>>
endobj
9 0 obj
<<
/Producer (Horde PDF)
/CreationDate (D:%d)
>>
endobj
10 0 obj
<<
/Type /Catalog
/Pages 1 0 R
/OpenAction [3 0 R /FitH null]
/PageLayout /OneColumn
>>
endobj
xref
0 11
0000000000 65535 f 
0000000877 00000 n 
0000003507 00000 n 
0000000009 00000 n 
0000000336 00000 n 
0000000540 00000 n 
0000000756 00000 n 
0000000970 00000 n 
0000001066 00000 n 
0000003620 00000 n 
0000003696 00000 n 
trailer
<<
/Size 11
/Root 10 0 R
/Info 9 0 R
>>
startxref
3800
%%EOF