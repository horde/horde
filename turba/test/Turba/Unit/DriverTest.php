<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../TestCase.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/apache Apache-like
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 */

class Turba_Unit_DriverTest extends Turba_TestCase
{
    public static function setUpBeforeClass()
    {
        self::createBasicTurbaSetup(new Horde_Test_Setup());
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        self::tearDownBasicTurbaSetup();
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        $this->contact = array(
            'name' => 'Jan Schneiderö',
            'namePrefix' => 'Mr.',
            'firstname' => 'Jan',
            'middlenames' => 'K.',
            'lastname' => 'Schneiderö',
            'email' => 'jan@horde.org',
            'alias' => 'yunosh',
            'homeAddress' => 'Schönestr. 15
33604 Bielefeld',
            'workStreet' => 'Hübschestr. 19',
            'workCity' => 'Köln',
            'workProvince' => 'Allgäu',
            'workPostalcode' => '33602',
            'workCountry' => 'DK',
            'homePhone' => '+49 521 555123',
            'workPhone' => '+49 521 555456',
            'cellPhone' => '+49 177 555123',
            'fax' => '+49 521 555789',
            'pager' => '+49 123 555789',
            'birthday' => '1971-10-01',
            'title' => 'Senior Developer (äöü)',
            'role' => 'Developer (äöü)',
            'company' => 'Horde Project',
            'department' => 'äöü',
            'notes' => 'A German guy (äöü)',
            'website' => 'http://janschneider.de',
            'timezone' => 'Europe/Berlin',
            'latitude' => '52.516276',
            'longitude' => '13.377778',
            'photo' => file_get_contents(__DIR__ . '/../fixtures/az.png'),
            'phototype' => 'image/png',
        );
        $this->driver = new Turba_Driver();
        $this->object = new Turba_Object($this->driver, $this->contact);
    }

    public function testVcard21()
    {
        $vcard = $this->driver->tovCard($this->object, '2.1');
        $this->assertEquals(
'BEGIN:VCARD
VERSION:2.1
FN;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:Jan Schneider=C3=B6
EMAIL;INTERNET:jan@horde.org
NICKNAME:yunosh
X-EPOCSECONDNAME:yunosh
LABEL;HOME;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:Sch=C3=B6nestr. 15=0D=0A=
 33604 Bielefeld
TEL;HOME;VOICE:+49 521 555123
TEL;WORK;VOICE:+49 521 555456
TEL;CELL;VOICE:+49 177 555123
TEL;FAX:+49 521 555789
TEL;PAGER:+49 123 555789
BDAY:1971-10-01
TITLE;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:Senior Developer (=C3=A4=C3=B6=C3=BC)
ROLE;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:Developer (=C3=A4=C3=B6=C3=BC)
NOTE;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:A German guy (=C3=A4=C3=B6=C3=BC)
URL:http://janschneider.de
TZ;VALUE=text:Europe/Berlin
GEO:13.377778,52.516276
PHOTO;ENCODING=b;TYPE=image/png:iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJAgMAAACd/+6DAAAACVBMVEW6ABZmZmYAAACMtcxCAAAAAXRSTlMAQObYZgAAABpJREFUCFtjYACBBgYmRgEIZmGBYAFGMAYBABVmAOEH9qP8AAAAAElFTkSuQmCC
N;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:Schneider=C3=B6;Jan;K.;Mr.;
ORG;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:Horde Project;=C3=A4=C3=B6=C3=BC
ADR;HOME;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:;;Sch=C3=B6nestr. 15=0D=0A=
 33604 Bielefeld;;;;
ADR;WORK;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:;;H=C3=BCbschestr. 19;K=C3=B6ln;Allg=C3=A4u;;D=C3=A4nemark
END:VCARD
',
            $vcard->exportvCalendar());
    }

    public function testVcard30()
    {
        $vcard = $this->driver->tovCard($this->object, '3.0');
        $this->assertEquals(
'BEGIN:VCARD
VERSION:3.0
FN:Jan Schneiderö
EMAIL;TYPE=INTERNET:jan@horde.org
NICKNAME:yunosh
X-EPOCSECONDNAME:yunosh
LABEL;TYPE=HOME:Schönestr. 15\n33604 Bielefeld
TEL;TYPE=HOME,VOICE:+49 521 555123
TEL;TYPE=WORK,VOICE:+49 521 555456
TEL;TYPE=CELL,VOICE:+49 177 555123
TEL;TYPE=FAX:+49 521 555789
TEL;TYPE=PAGER:+49 123 555789
BDAY:1971-10-01
TITLE:Senior Developer (äöü)
ROLE:Developer (äöü)
NOTE:A German guy (äöü)
URL:http://janschneider.de
TZ;VALUE=text:Europe/Berlin
GEO:52.516276;13.377778
PHOTO;ENCODING=b;TYPE=image/png:iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJAgMAAACd/+6
 DAAAACVBMVEW6ABZmZmYAAACMtcxCAAAAAXRSTlMAQObYZgAAABpJREFUCFtjYACBBgYmRgEIZm
 GBYAFGMAYBABVmAOEH9qP8AAAAAElFTkSuQmCC
N:Schneiderö;Jan;K.;Mr.;
ORG:Horde Project;äöü
ADR;TYPE=HOME:;;Schönestr. 15\n33604 Bielefeld;;;;
ADR;TYPE=WORK:;;Hübschestr. 19;Köln;Allgäu;;Dänemark
END:VCARD
',
            $vcard->exportvCalendar());
    }

    public function testBug9207()
    {
        $driver = clone $this->driver;
        $driver->alternativeName = 'company';
        $driver->map['name'] = array(
            'fields' => array('namePrefix', 'firstname', 'middlenames',
                              'lastname', 'nameSuffix'),
            'format' => '%s %s %s %s %s');
        $contact = $this->contact;
        unset($contact['name']);
        $object = new Turba_Object($driver, $contact);
        $vcard = $this->driver->tovCard($object, '3.0');
        $this->assertEquals(
'BEGIN:VCARD
VERSION:3.0
EMAIL;TYPE=INTERNET:jan@horde.org
NICKNAME:yunosh
X-EPOCSECONDNAME:yunosh
LABEL;TYPE=HOME:Schönestr. 15\n33604 Bielefeld
TEL;TYPE=HOME,VOICE:+49 521 555123
TEL;TYPE=WORK,VOICE:+49 521 555456
TEL;TYPE=CELL,VOICE:+49 177 555123
TEL;TYPE=FAX:+49 521 555789
TEL;TYPE=PAGER:+49 123 555789
BDAY:1971-10-01
TITLE:Senior Developer (äöü)
ROLE:Developer (äöü)
NOTE:A German guy (äöü)
URL:http://janschneider.de
TZ;VALUE=text:Europe/Berlin
GEO:52.516276;13.377778
PHOTO;ENCODING=b;TYPE=image/png:iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJAgMAAACd/+6
 DAAAACVBMVEW6ABZmZmYAAACMtcxCAAAAAXRSTlMAQObYZgAAABpJREFUCFtjYACBBgYmRgEIZm
 GBYAFGMAYBABVmAOEH9qP8AAAAAElFTkSuQmCC
N:Schneiderö;Jan;K.;Mr.;
FN:Mr. Jan K. Schneiderö
ORG:Horde Project;äöü
ADR;TYPE=HOME:;;Schönestr. 15\n33604 Bielefeld;;;;
ADR;TYPE=WORK:;;Hübschestr. 19;Köln;Allgäu;;Dänemark
END:VCARD
',
            $vcard->exportvCalendar());
    }

    public function tearDown()
    {
    }
}
