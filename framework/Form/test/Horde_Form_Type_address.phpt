--TEST--
Horde_Form_Type_address tests
--FILE--
<?php

require 'Horde/Autoloader.php';
require __DIR__ . '/../Form.php';

$addresses = array(
    // UK addresses.
    '11 Foo Bar
2nd Row
London W3 8JN',
    '999 Church Street
London
N9 9HT',

    // German addresses.
    'Hauptstr. 1
D-11111 Stadt',
    'Nebenweg 13
22222 Hintertupfing
Germany',

    // Canadian addresses.
    '1000 Sesame Street
Vancouver, BC V6C 3P1',

    // U.S. addresses.
    '99 Foo Street
Boston, MA 02141
USA',
    'First line
101 Main Road
Chelmsford, Massachusetts 01824',
    // This one cannot be parsed correctly:
    '3000 Woodstock Boulevard
Portland, Oregon
USA',

    // Various countries.
    'Foo-Bar-Str. 99
12345 Hinterm Wald',
    '33602 Bielefeld',
    'Some first line
A 2nd address line 51
33333 Somewhere',
    'Straat 123
9717 Groningen
Unknown Country'
);

foreach ($addresses as $address) {
    var_dump(Horde_Form_Type_address::parse($address));
}

?>
--EXPECT--
array(4) {
  ["country"]=>
  string(2) "uk"
  ["zip"]=>
  string(6) "W3 8JN"
  ["street"]=>
  string(18) "11 Foo Bar
2nd Row"
  ["city"]=>
  string(6) "London"
}
array(4) {
  ["country"]=>
  string(2) "uk"
  ["zip"]=>
  string(6) "N9 9HT"
  ["street"]=>
  string(17) "999 Church Street"
  ["city"]=>
  string(6) "London"
}
array(4) {
  ["street"]=>
  string(11) "Hauptstr. 1"
  ["country"]=>
  string(2) "de"
  ["zip"]=>
  string(5) "11111"
  ["city"]=>
  string(5) "Stadt"
}
array(4) {
  ["street"]=>
  string(11) "Nebenweg 13"
  ["country"]=>
  string(2) "de"
  ["zip"]=>
  string(5) "22222"
  ["city"]=>
  string(13) "Hintertupfing"
}
array(5) {
  ["country"]=>
  string(2) "ca"
  ["street"]=>
  string(18) "1000 Sesame Street"
  ["city"]=>
  string(9) "Vancouver"
  ["state"]=>
  string(2) "BC"
  ["zip"]=>
  string(7) "V6C 3P1"
}
array(5) {
  ["country"]=>
  string(2) "us"
  ["street"]=>
  string(13) "99 Foo Street"
  ["city"]=>
  string(6) "Boston"
  ["state"]=>
  string(2) "MA"
  ["zip"]=>
  string(5) "02141"
}
array(5) {
  ["country"]=>
  string(2) "us"
  ["street"]=>
  string(24) "First line
101 Main Road"
  ["city"]=>
  string(10) "Chelmsford"
  ["state"]=>
  string(13) "Massachusetts"
  ["zip"]=>
  string(5) "01824"
}
array(3) {
  ["street"]=>
  string(16) "Portland, Oregon"
  ["zip"]=>
  string(4) "3000"
  ["city"]=>
  string(19) "Woodstock Boulevard"
}
array(3) {
  ["street"]=>
  string(15) "Foo-Bar-Str. 99"
  ["zip"]=>
  string(5) "12345"
  ["city"]=>
  string(12) "Hinterm Wald"
}
array(2) {
  ["zip"]=>
  string(5) "33602"
  ["city"]=>
  string(9) "Bielefeld"
}
array(3) {
  ["street"]=>
  string(37) "Some first line
A 2nd address line 51"
  ["zip"]=>
  string(5) "33333"
  ["city"]=>
  string(9) "Somewhere"
}
array(3) {
  ["street"]=>
  string(26) "Straat 123
Unknown Country"
  ["zip"]=>
  string(4) "9717"
  ["city"]=>
  string(9) "Groningen"
}
