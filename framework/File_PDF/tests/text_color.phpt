--TEST--
File_PDF: Text colors.
--FILE--
<?php

require_once dirname(__FILE__) . '/../PDF.php';

// Set up the pdf object.
$pdf = &File_PDF::factory();
// Deactivate compression.
$pdf->setCompression(false);
// Start the document.
$pdf->open();
// Start a page.
$pdf->addPage();
// Set font to Helvetica bold 24 pt.
$pdf->setFont('Helvetica', 'B', 48);
// Set colors.
$pdf->setDrawColor('rgb', 50, 0, 0);
$pdf->setTextColor('rgb', 0, 50, 0);
$pdf->setFillColor('rgb', 0, 0, 50);
// Write text.
$pdf->cell(0, 50, 'Hello Colors', 1, 0, 'C', 1);
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
<</Length 174>>
stream
2 J
0.57 w
BT /F1 48.00 Tf ET
50.000 0.000 0.000 RG
0.000 0.000 50.000 rg
28.35 813.54 538.58 -141.73 re B q 0.000 50.000 0.000 rg BT 156.28 728.27 Td (Hello Colors) Tj ET Q

endstream
endobj
1 0 obj
<</Type /Pages
/Kids [3 0 R ]
/Count 1
/MediaBox [0 0 595.28 841.89]
>>
endobj
5 0 obj
<</Type /Font
/BaseFont /Helvetica-Bold
/Subtype /Type1
/Encoding /WinAnsiEncoding
>>
endobj
2 0 obj
<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]
/Font <<
/F1 5 0 R
>>
>>
endobj
6 0 obj
<<
/Producer (Horde PDF)
/CreationDate (D:%d)
>>
endobj
7 0 obj
<<
/Type /Catalog
/Pages 1 0 R
/OpenAction [3 0 R /FitH null]
/PageLayout /OneColumn
>>
endobj
xref
0 8
0000000000 65535 f 
0000000310 00000 n 
0000000498 00000 n 
0000000009 00000 n 
0000000087 00000 n 
0000000397 00000 n 
0000000586 00000 n 
0000000662 00000 n 
trailer
<<
/Size 8
/Root 7 0 R
/Info 6 0 R
>>
startxref
765
%%EOF
