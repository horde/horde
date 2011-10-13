--TEST--
Turba_Driver::toVcard() test.
--FILE--
<?php

require dirname(__FILE__) . '/../Object.php';
require dirname(__FILE__) . '/../Driver.php';

$contact = array(
  'name' => 'Jan Schneider�',
  'namePrefix' => 'Mr.',
  'firstname' => 'Jan',
  'middlenames' => 'K.',
  'lastname' => 'Schneider�',
  'email' => 'jan@horde.org',
  'alias' => 'yunosh',
  'homeAddress' => 'Sch�nestr. 15
33604 Bielefeld',
  'workStreet' => 'H�bschestr. 19',
  'workCity' => 'K�ln',
  'workProvince' => 'Allg�u',
  'workPostalcode' => '33602',
  'workCountry' => 'D�nemark',
  'homePhone' => '+49 521 555123',
  'workPhone' => '+49 521 555456',
  'cellPhone' => '+49 177 555123',
  'fax' => '+49 521 555789',
  'pager' => '+49 123 555789',
  'birthday' => '1971-10-01',
  'title' => 'Senior Developer (���)',
  'role' => 'Developer (���)',
  'company' => 'Horde Project',
  'department' => '���',
  'notes' => 'A German guy (���)',
  'website' => 'http://janschneider.de',
  'timezone' => 'Europe/Berlin',
  'latitude' => '52.516276',
  'longitude' => '13.377778',
  'photo' => file_get_contents(dirname(__FILE__) . '/az.png'),
  'phototype' => 'image/png',
);

$driver = new Turba_Driver(array());
$object = new Turba_Object($driver, $contact);
$vcard = $driver->tovCard($object, '2.1');
echo $vcard->exportvCalendar() . "\n";
$vcard = $driver->tovCard($object, '3.0');
echo $vcard->exportvCalendar() . "\n";

// Test for bug #9207
@define('TURBA_BASE', '/non/existant');
$driver->alternativeName = 'company';
$driver->map['name'] = array('fields' => array('namePrefix', 'firstname',
                                               'middlenames', 'lastname',
                                               'nameSuffix'),
                             'format' => '%s %s %s %s %s');
unset($contact['name']);
$object = new Turba_Object($driver, $contact);
$vcard = $driver->tovCard($object, '3.0');
echo $vcard->exportvCalendar();

?>
--EXPECT--
BEGIN:VCARD
VERSION:2.1
FN;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:Jan Schneider=F6
EMAIL:jan@horde.org
NICKNAME:yunosh
LABEL;HOME;ENCODING=QUOTED-PRINTABLE;CHARSET=ISO-8859-1:Sch=F6nestr. 15=0D=0A=
33604 Bielefeld
TEL;HOME:+49 521 555123
TEL;WORK:+49 521 555456
TEL;CELL:+49 177 555123
TEL;FAX:+49 521 555789
TEL;PAGER:+49 123 555789
BDAY:1971-10-01
TITLE;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:Senior Developer (=E4=F6=FC)
ROLE;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:Developer (=E4=F6=FC)
NOTE;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:A German guy (=E4=F6=FC)
URL:http://janschneider.de
TZ;VALUE=text:Europe/Berlin
GEO:13.377778,52.516276
PHOTO;ENCODING=b;TYPE=image/png:iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJAgMAAACd/+6DAAAACVBMVEW6ABZmZmYAAACMtcxCAAAAAXRSTlMAQObYZgAAABpJREFUCFtjYACBBgYmRgEIZmGBYAFGMAYBABVmAOEH9qP8AAAAAElFTkSuQmCC
N;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:Schneider=F6;Jan;K.;Mr.;
ORG;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:Horde Project;=E4=F6=FC
ADR;HOME;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:;;Sch=F6nestr. 15=0D=0A=
33604 Bielefeld;;;;
ADR;WORK;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:;;H=FCbschestr. 19;K=F6ln;Allg=E4u;;D=E4nemark
END:VCARD

BEGIN:VCARD
VERSION:3.0
FN:Jan Schneiderö
EMAIL:jan@horde.org
NICKNAME:yunosh
LABEL;TYPE=HOME:Schönestr. 15\n33604 Bielefeld
TEL;TYPE=HOME:+49 521 555123
TEL;TYPE=WORK:+49 521 555456
TEL;TYPE=CELL:+49 177 555123
TEL;TYPE=FAX:+49 521 555789
TEL;TYPE=PAGER:+49 123 555789
BDAY:1971-10-01
TITLE:Senior Developer (äöü)
ROLE:Developer (äöü)
NOTE:A German guy (äöü)
URL:http://janschneider.de
TZ;VALUE=text:Europe/Berlin
GEO:52.516276;13.377778
PHOTO;ENCODING=b;TYPE=image/png:wolQTkcNChoKAAAADUlIRFIAAAAJAAAACQIDAAAAwp3
 Dv8OuwoMAAAAJUExURcK6ABZmZmYAAADCjMK1w4xCAAAAAXRSTlMAQMOmw5hmAAAAGklEQVQIW2
 NgAMKBBgYmRgEIZmHCgWABRjAGAQAVZgDDoQfDtsKjw7wAAAAASUVORMKuQmDCgg==
N:Schneiderö;Jan;K.;Mr.;
ORG:Horde Project;äöü
ADR;TYPE=HOME:;;Schönestr. 15\n33604 Bielefeld;;;;
ADR;TYPE=WORK:;;Hübschestr. 19;Köln;Allgäu;;Dänemark
END:VCARD

BEGIN:VCARD
VERSION:3.0
EMAIL:jan@horde.org
NICKNAME:yunosh
LABEL;TYPE=HOME:Schönestr. 15\n33604 Bielefeld
TEL;TYPE=HOME:+49 521 555123
TEL;TYPE=WORK:+49 521 555456
TEL;TYPE=CELL:+49 177 555123
TEL;TYPE=FAX:+49 521 555789
TEL;TYPE=PAGER:+49 123 555789
BDAY:1971-10-01
TITLE:Senior Developer (äöü)
ROLE:Developer (äöü)
NOTE:A German guy (äöü)
URL:http://janschneider.de
TZ;VALUE=text:Europe/Berlin
GEO:52.516276;13.377778
PHOTO;ENCODING=b;TYPE=image/png:wolQTkcNChoKAAAADUlIRFIAAAAJAAAACQIDAAAAwp3
 Dv8OuwoMAAAAJUExURcK6ABZmZmYAAADCjMK1w4xCAAAAAXRSTlMAQMOmw5hmAAAAGklEQVQIW2
 NgAMKBBgYmRgEIZmHCgWABRjAGAQAVZgDDoQfDtsKjw7wAAAAASUVORMKuQmDCgg==
N:Schneiderö;Jan;K.;Mr.;
FN:Mr. Jan K. Schneiderö
ORG:Horde Project;äöü
ADR;TYPE=HOME:;;Schönestr. 15\n33604 Bielefeld;;;;
ADR;TYPE=WORK:;;Hübschestr. 19;Köln;Allgäu;;Dänemark
END:VCARD
