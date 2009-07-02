--TEST--
Horde_String::wordwrap() tests
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Util.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/String.php';

// Test default parameters and break character.
$string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 75, "\n", false, 'utf-8') . "\n\n";
echo Horde_String::wordwrap($string, 30, "\n  ", false, 'utf-8') . "\n\n";

// Test existing line breaks.
$string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit.\nAliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 75, "\n", false, 'utf-8') . "\n\n";
$string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm\nsöllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 75, "\n", false, 'utf-8') . "\n\n";
$string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin\nfäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 75, "\n", false, 'utf-8') . "\n\n";

// Test overlong words and word cut.
$string = "Löremipsümdölörsitämet, cönsectetüerädipiscingelit.";
echo Horde_String::wordwrap($string, 15, "\n", false, 'utf-8') . "\n\n";
$string = "Löremipsümdölörsitämet, cönsectetüerädipiscingelit.";
echo Horde_String::wordwrap($string, 15, "\n", true, 'utf-8') . "\n\n";

// Test whitespace at wrap width.
$string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing";
echo Horde_String::wordwrap($string, 27, "\n", false, 'utf-8') . "\n\n";
echo Horde_String::wordwrap($string, 28, "\n", false, 'utf-8') . "\n\n";

// Test line folding.
$string = "Löremipsümdölörsitämet, cönsectetüerädipiscingelit.";
echo Horde_String::wordwrap($string, 15, "\n", true, 'utf-8', true) . "\n\n";
$string = "Lörem ipsüm dölör sit ämet,  cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true) . "\n\n";
$string = "Lörem ipsüm dölör sit; ämet:  cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true) . "\n\n";
$string = "Lörem ipsüm dölör sit; ämet:cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true) . "\n\n";
$string = "Lörem ipsüm dölör sit; ämet;  cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true) . "\n\n";
$string = "Lörem ipsüm dölör sit; ämet;cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
echo Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true) . "\n\n";

?>
--EXPECT--
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin fäücibüs mäüris ämet.

Lörem ipsüm dölör sit ämet,
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
  mäüris ämet.

Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit.
Aliqüäm söllicitüdin fäücibüs mäüris ämet.

Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin fäücibüs mäüris ämet.

Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin
fäücibüs mäüris ämet.

Löremipsümdölörsitämet,
cönsectetüerädipiscingelit.

Löremipsümdölör
sitämet,
cönsectetüerädi
piscingelit.

Lörem ipsüm dölör sit ämet,
cönsectetüer ädipiscing

Lörem ipsüm dölör sit ämet,
cönsectetüer ädipiscing

Löremipsümdölör
sitämet,
 cönsectetüeräd
ipiscingelit.

Lörem ipsüm dölör sit ämet,
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
 mäüris ämet.

Lörem ipsüm dölör sit;
 ämet:
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
 mäüris ämet.

Lörem ipsüm dölör sit;
 ämet:cönsectetüer ädipiscing
 elit.  Aliqüäm söllicitüdin
 fäücibüs mäüris ämet.

Lörem ipsüm dölör sit;
 ämet;
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
 mäüris ämet.

Lörem ipsüm dölör sit;
 ämet;cönsectetüer ädipiscing
 elit.  Aliqüäm söllicitüdin
 fäücibüs mäüris ämet.
