--TEST--
File_PDF: Automatic page break test
--FILE--
<?php

require_once dirname(__FILE__) . '/../PDF.php';

// Set up the pdf object.
$pdf = &File_PDF::factory(array('format' => array(50, 50), 'unit' => 'pt'));
// Deactivate compression.
$pdf->setCompression(false);
// Set margins.
$pdf->setMargins(0, 0);
// Enable automatic page breaks.
$pdf->setAutoPageBreak(true);
// Start the document.
$pdf->open();
// Start a page.
$pdf->addPage();
// Set font to Courier 8 pt.
$pdf->setFont('Courier', '', 10);
// Write 7 lines
$pdf->write(10, "Hello\nHello\nHello\nHello\nHello\nHello\nHello\n");
// Print the generated file.
echo $pdf->getOutput();

?>
--EXPECTF--
%PDF-1.3
3 0 obj
<</Type /Page
/Parent 1 0 R
/Resources 2 0 R
/Contents 4 0 R>>
endobj
4 0 obj
<</Length 184>>
stream
2 J
0.57 w
BT /F1 10.00 Tf ET
BT 2.83 42.00 Td (Hello) Tj ET
BT 2.83 32.00 Td (Hello) Tj ET
BT 2.83 22.00 Td (Hello) Tj ET
BT 2.83 12.00 Td (Hello) Tj ET
BT 2.83 2.00 Td (Hello) Tj ET

endstream
endobj
5 0 obj
<</Type /Page
/Parent 1 0 R
/Resources 2 0 R
/Contents 6 0 R>>
endobj
6 0 obj
<</Length 92>>
stream
2 J
0.57 w
BT /F1 10.00 Tf ET
BT 2.83 42.00 Td (Hello) Tj ET
BT 2.83 32.00 Td (Hello) Tj ET

endstream
endobj
1 0 obj
<</Type /Pages
/Kids [3 0 R 5 0 R ]
/Count 2
/MediaBox [0 0 50.00 50.00]
>>
endobj
7 0 obj
<</Type /Font
/BaseFont /Courier
/Subtype /Type1
/Encoding /WinAnsiEncoding
>>
endobj
2 0 obj
<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]
/Font <<
/F1 7 0 R
>>
>>
endobj
8 0 obj
<<
/Producer (Horde PDF)
/CreationDate (D:%d)
>>
endobj
9 0 obj
<<
/Type /Catalog
/Pages 1 0 R
/OpenAction [3 0 R /FitH null]
/PageLayout /OneColumn
>>
endobj
xref
0 10
0000000000 65535 f 
0000000538 00000 n 
0000000723 00000 n 
0000000009 00000 n 
0000000087 00000 n 
0000000320 00000 n 
0000000398 00000 n 
0000000629 00000 n 
0000000811 00000 n 
0000000887 00000 n 
trailer
<<
/Size 10
/Root 9 0 R
/Info 8 0 R
>>
startxref
990
%%EOF
