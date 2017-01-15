<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/apache Apache-like
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 */
class Turba_Unit_ImportTest extends Turba_TestCase
{
    protected static $nameMap = array(
        'name' => array(
            'fields' => array('namePrefix', 'firstname', 'middlenames',
                              'lastname', 'nameSuffix'),
            'attribute' => 'object_name',
            'format' => '%s %s %s %s %s'
        )
    );

    protected static $emailMap = array(
        'homeEmail' => 'object_homeemail',
        'workEmail' => 'object_workemail'
    );

    public static function setUpBeforeClass()
    {
        self::createBasicTurbaSetup(new Horde_Test_Setup());
        parent::setUpBeforeClass();
        setlocale(LC_MESSAGES, 'C');
    }

    public static function tearDownAfterClass()
    {
        self::tearDownBasicTurbaSetup();
        parent::tearDownAfterClass();
        setlocale(LC_MESSAGES, null);
    }

    public function testImportVcard21()
    {
        $vcard =
'BEGIN:VCARD
VERSION:2.1
FN;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
Jan Schneider=F6
EMAIL:jan@horde.org
NICKNAME:yunosh
TEL;HOME:+49 521 555123
TEL;WORK:+49 521 555456
TEL;WORK:+49 521 999999
TEL;CELL:+49 177 555123
TEL;FAX:+49 521 555789
TEL;PAGER:+49 123 555789
BDAY:1971-10-01
TITLE;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
Senior Developer (=E4=F6=FC)
ROLE;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
Developer (=E4=F6=FC)
NOTE;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
A German guy (=E4=F6=FC)
URL:http://janschneider.de
N;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
Schneider=F6;Jan;K.;Mr.;
ORG;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
Horde Project;=E4=F6=FC
ADR;HOME;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
;;Sch=F6nestr. 15;Bielefeld;;33604;
ADR;WORK;CHARSET=ISO-8859-1;ENCODING=QUOTED-PRINTABLE:=
;;H=FCbschestr. 19;K=F6ln;Allg=E4u;;D=E4nemark
TZ;VALUE=text:+02:00; Europe/Berlin
GEO:13.377778,52.516276
BODY:
END:VCARD
';

        $this->assertEquals(
            array(
                'name' => 'Jan Schneiderö',
                'email' => 'jan@horde.org',
                'emails' => 'jan@horde.org',
                'nickname' => 'yunosh',
                'alias' => 'yunosh',
                'homePhone' => '+49 521 555123',
                'workPhone' => '+49 521 555456',
                'cellPhone' => '+49 177 555123',
                'fax' => '+49 521 555789',
                'pager' => '+49 123 555789',
                'birthday' => '1971-10-01',
                'title' => 'Senior Developer (äöü)',
                'role' => 'Developer (äöü)',
                'notes' => 'A German guy (äöü)',
                'website' => 'http://janschneider.de',
                'lastname' => 'Schneiderö',
                'firstname' => 'Jan',
                'middlenames' => 'K.',
                'namePrefix' => 'Mr.',
                'company' => 'Horde Project',
                'department' => 'äöü',
                'homeAddress' => 'Schönestr. 15
Bielefeld 33604',
                'homeStreet' => 'Schönestr. 15',
                'homeCity' => 'Bielefeld',
                'homePostalCode' => '33604',
                'workAddress' => 'Hübschestr. 19
Köln, Allgäu
Dänemark',
                'workStreet' => 'Hübschestr. 19',
                'workCity' => 'Köln',
                'workProvince' => 'Allgäu',
                'workCountry' => 'Dänemark',
                'timezone' => 'Europe/Berlin',
                'latitude' => 52.516276,
                'longitude' => 13.377778,
                'phone' => '+49 521 999999',
            ),
            $this->toHash($vcard)
        );
    }

    public function testImportVcard30()
    {
        $vcard =
'BEGIN:VCARD
VERSION:3.0
FN:Jan Schneiderö
EMAIL:jan@horde.org
NICKNAME:yunosh
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
N:Schneiderö;Jan;K.;Mr.;
ORG:Horde Project;äöü
ADR;TYPE=HOME:;;Schönestr. 15;Bielefeld;;33604;;
ADR;TYPE=WORK:;;Hübschestr. 19;Köln;Allgäu;;Dänemark
TZ;VALUE=text:+02:00; Europe/Berlin
GEO:52.516276;13.377778
BODY:
END:VCARD
';

        $this->assertEquals(
            array(
                'name' => 'Jan Schneiderö',
                'email' => 'jan@horde.org',
                'emails' => 'jan@horde.org',
                'nickname' => 'yunosh',
                'alias' => 'yunosh',
                'homePhone' => '+49 521 555123',
                'workPhone' => '+49 521 555456',
                'cellPhone' => '+49 177 555123',
                'fax' => '+49 521 555789',
                'pager' => '+49 123 555789',
                'birthday' => '1971-10-01',
                'title' => 'Senior Developer (äöü)',
                'role' => 'Developer (äöü)',
                'notes' => 'A German guy (äöü)',
                'website' => 'http://janschneider.de',
                'lastname' => 'Schneiderö',
                'firstname' => 'Jan',
                'middlenames' => 'K.',
                'namePrefix' => 'Mr.',
                'company' => 'Horde Project',
                'department' => 'äöü',
                'homeAddress' => 'Schönestr. 15
Bielefeld 33604',
                'homeStreet' => 'Schönestr. 15',
                'homeCity' => 'Bielefeld',
                'homePostalCode' => '33604',
                'workAddress' => 'Hübschestr. 19
Köln, Allgäu
Dänemark',
                'workStreet' => 'Hübschestr. 19',
                'workCity' => 'Köln',
                'workProvince' => 'Allgäu',
                'workCountry' => 'Dänemark',
                'timezone' => 'Europe/Berlin',
                'latitude' => 52.516276,
                'longitude' => 13.377778,
            ),
            $this->toHash($vcard)
        );

        $vcard =
'BEGIN:VCARD
VERSION:3.0
ITEM2.ADR;TYPE=HOME;TYPE=pref:;;Straße;Ort;;12345;Deutsch
 land
TEL;TYPE=HOME;TYPE=VOICE:07071-996715
ITEM3.ADR;TYPE=WORK:;;Work-Straße;Work-Ort;;72070;Deutschland
BDAY;VALUE=date:1980-04-19
UID:b33393c4-98a1-4e1a-8f5c-d29459406093
REV:2013-01-14T15:35:00+00:00
TEL;TYPE=HOME;TYPE=FAX:0800-12345
TEL;TYPE=WORK;TYPE=VOICE:0900-12345
N:Naß;Anna Christina;;;
PRODID:-//Apple Inc.//iOS 6.0.1//EN
FN:Anna Christina Naß
ORG:Organization;
ITEM1.X-ABLABEL:_$!<Other>!$_
ITEM1.EMAIL;TYPE=INTERNET;TYPE=pref:email@domain.tld
TEL;TYPE=CELL;TYPE=VOICE;TYPE=pref:0123-123456
END:VCARD
';

        $this->assertEquals(
            array(
                'homeAddress' => 'Straße
Ort 12345
Deutschland',
                'homeStreet' => 'Straße',
                'homeCity' => 'Ort',
                'homePostalCode' => '12345',
                'homeCountry' => 'Deutschland',
                'commonAddress' => 'Straße
Ort 12345
Deutschland',
                'commonStreet' => 'Straße',
                'commonCity' => 'Ort',
                'commonPostalCode' => '12345',
                'commonCountry' => 'Deutschland',
                'homePhone' => '07071-996715',
                'workAddress' => 'Work-Straße
Work-Ort 72070
Deutschland',
                'workStreet' => 'Work-Straße',
                'workCity' => 'Work-Ort',
                'workPostalCode' => '72070',
                'workCountry' => 'Deutschland',
                'birthday' => '1980-04-19',
                'homeFax' => '0800-12345',
                'workPhone' => '0900-12345',
                'lastname' => 'Naß',
                'firstname' => 'Anna Christina',
                'name' => 'Anna Christina Naß',
                'company' => 'Organization',
                'department' => '',
                'email' => 'email@domain.tld',
                'emails' => 'email@domain.tld',
                'cellPhone' => '0123-123456',
                '__uid' => 'b33393c4-98a1-4e1a-8f5c-d29459406093',
            ),
            $this->toHash($vcard)
        );
    }

    public function testImportFullName()
    {
        $vcard =
'BEGIN:VCARD
VERSION:3.0
FN:Jan Schneider
N:Schneider;Jan;K.;Mr.;
END:VCARD
';

        $this->assertEquals(
            array(
                'name' => 'Jan Schneider',
                'lastname' => 'Schneider',
                'firstname' => 'Jan',
                'middlenames' => 'K.',
                'namePrefix' => 'Mr.',
            ),
            $this->toHash($vcard)
        );

        $this->assertEquals(
            array(
                'lastname' => 'Schneider',
                'firstname' => 'Jan',
                'middlenames' => 'K.',
                'namePrefix' => 'Mr.',
                'name' => 'Jan Schneider',
            ),
            $this->toHash($vcard, self::$nameMap)
        );
    }

    public function testImportNameParts()
    {
        $vcard =
'BEGIN:VCARD
VERSION:3.0
N:Schneider;Jan;K.;Mr.;
END:VCARD
';

        $this->assertEquals(
            array(
                'lastname' => 'Schneider',
                'firstname' => 'Jan',
                'middlenames' => 'K.',
                'namePrefix' => 'Mr.',
                'name' => 'Mr. Jan K. Schneider',
            ),
            $this->toHash($vcard, self::$nameMap)
        );
    }

    public function testImportPhone()
    {
        $vcard =
'BEGIN:VCARD
VERSION:2.1
REV:20080523T071425Z
N:B;A;;;
TEL;CELL:1
TEL;VOICE:4
X-CLASS:private
TEL;CELL;HOME:2
TEL;CELL;WORK:3
TEL;VOICE;HOME:5
TEL;VOICE;WORK:6
END:VCARD
';

        $this->assertEquals(
            array(
                'lastname' => 'B',
                'firstname' => 'A',
                'cellPhone' => '1',
                'phone' => '4',
                'homeCellPhone' => '2',
                'workCellPhone' => '3',
                'homePhone' => '5',
                'workPhone' => '6',
                'name' => 'A B',
            ),
            $this->toHash($vcard)
        );

        $vcard =
'BEGIN:VCARD
VERSION:2.1
N:Mustermann;Maximilian
FN;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:Maximilian Mustermann
NICKNAME:
TEL;HOME;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:+49 123 28799666
TEL;WORK;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:+49 123 28799626
TEL;CELL;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:+49 123 28799666
TEL;PAGER;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:+49 123 28799666
TEL;FAX;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:+49 123 1234564
EMAIL;INTERNET:
EMAIL;INTERNET;HOME:
EMAIL;INTERNET;HOME;X-FUNAMBOL-INSTANTMESSENGER:
ADR;HOME;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:;;Carl-Bantzer-Str. 99;Foobar;Sachsen;01234;
URL;HOME:
ADR;WORK:;;;;;;
ADR:;
ORG:;
TITLE:
URL;WORK:
BDAY:
NOTE:
END:VCARD
';

        $this->assertEquals(
            array(
                'lastname' => 'Mustermann',
                'firstname' => 'Maximilian',
                'name' => 'Maximilian Mustermann',
                'homePhone' => '+49 123 28799666',
                'workPhone' => '+49 123 28799626',
                'cellPhone' => '+49 123 28799666',
                'pager' => '+49 123 28799666',
                'fax' => '+49 123 1234564',
                'nickname' => '',
                'alias' => '',
                'email' => '',
                'emails' => '',
                'homeEmail' => '',
                'homeAddress' => 'Carl-Bantzer-Str. 99
Foobar, Sachsen 01234',
                'homeStreet' => 'Carl-Bantzer-Str. 99',
                'homeCity' => 'Foobar',
                'homeProvince' => 'Sachsen',
                'homePostalCode' => '01234',
                'homeWebsite' => '',
                'workAddress' => '',
                'commonAddress' => '',
                'company' => '',
                'department' => '',
                'title' => '',
                'workWebsite' => '',
                'birthday' => '',
                'notes' => '',
            ),
            $this->toHash($vcard, self::$emailMap)
        );
    }

    public function testImportAddress()
    {
        $vcard =
'BEGIN:VCARD
VERSION:2.1
N:Lastname;Firstname;;;
FN:Lastname, Firstname
TITLE:
ORG:Company Name;
BDAY:
TEL;HOME;VOICE;X-Synthesis-Ref1:(xxx) xxx-xxxx
TEL;WORK;VOICE;X-Synthesis-Ref1:(xxx) xxx-xxxx
TEL;CELL;VOICE;X-Synthesis-Ref1:(xxx) xxx-xxxx
EMAIL:email@domain.com
URL:
CATEGORIES:Friends
NOTE;ENCODING=QUOTED-PRINTABLE:
EIN: xx-xxxxxxx
ADR;HOME:;;Street address;City;St;12345;USA
ADR;WORK:;;Street address;City;St;12345;USA
PHOTO:
END:VCARD
';

        $this->assertEquals(
            array(
                'lastname' => 'Lastname',
                'firstname' => 'Firstname',
                'name' => 'Lastname, Firstname',
                'title' => '',
                'company' => 'Company Name',
                'department' => '',
                'birthday' => '',
                'homePhone' => '(xxx) xxx-xxxx',
                'workPhone' => '(xxx) xxx-xxxx',
                'cellPhone' => '(xxx) xxx-xxxx',
                'email' => 'email@domain.com',
                'emails' => 'email@domain.com',
                'website' => '',
                '__tags' => array('Friends'),
                'businessCategory' => 'Friends',
                'notes' => '',
                'homeAddress' => 'Street address
City, St 12345
USA',
                'homeStreet' => 'Street address',
                'homeCity' => 'City',
                'homeProvince' => 'St',
                'homePostalCode' => '12345',
                'homeCountry' => 'USA',
                'workAddress' => 'Street address
City, St 12345
USA',
                'workStreet' => 'Street address',
                'workCity' => 'City',
                'workProvince' => 'St',
                'workPostalCode' => '12345',
                'workCountry' => 'USA',
            ),
            $this->toHash($vcard)
        );
    }

    public function testImportPhoto()
    {
        $vcard =
'BEGIN:VCARD
FN:Jan Schneider
N:Schneider;Jan;;;
PHOTO;ENCODING=b;TYPE=image/png:iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJAgMAAACd/+6
 DAAAACVBMVEW6ABZmZmYAAACMtcxCAAAAAXRSTlMAQObYZgAAABlJREFUeAFjAIMGBiZGAQhmYY
 FgAUYwBgEAFWYA4dv5cHYAAAAASUVORK5CYII=
UID:nhCnPyv0u7
VERSION:2.1
END:VCARD
';
        $hash = $this->toHash($vcard);
        $this->assertStringEqualsFile(
            __DIR__ . '/../fixtures/az.png',
            $hash['photo']
        );
    }

    public function testImportEmail()
    {
        $vcard =
'BEGIN:VCARD
FN:Jan Schneider
N:Schneider;Jan;;;
EMAIL;WORK:work@example.com
EMAIL;HOME:home@example.com
EMAIL:mail@example.com
EMAIL;PREF:pref@example.com
UID:nhCnPyv0u7
VERSION:2.1
END:VCARD
';

        $this->assertEquals(
            array(
                'name' => 'Jan Schneider',
                'lastname' => 'Schneider',
                'firstname' => 'Jan',
                'workEmail' => 'work@example.com',
                'emails' => 'work@example.com,home@example.com,mail@example.com,pref@example.com',
                'homeEmail' => 'home@example.com',
                'email' => 'pref@example.com',
                '__uid' => 'nhCnPyv0u7',
            ),
            $this->toHash($vcard, self::$emailMap)
        );
    }

    public function testImportInvalidBinaryEncoding()
    {
        $vcard =
'BEGIN:VCARD
VERSION:3.0
PRODID:-//Synthesis AG//NONSGML SyncML Engine V3.1.6.10//EN
REV:20081004T151032
N:McTester;Testie;;;
FN:Testie McTester
ORG:Testers Inc;
TEL;TYPE=VOICE,CELL,X-Synthesis-Ref0:+44 775550555
TEL;TYPE=HOME,VOICE,X-Synthesis-Ref1:+44 205550555
TEL;TYPE=WORK,VOICE,X-Synthesis-Ref2:+44 205550556
EMAIL;TYPE=HOME,INTERNET,X-Synthesis-Ref0:test@example.org
EMAIL;TYPE=WORK,INTERNET,X-Synthesis-Ref1:test@example.com
ADR;TYPE=HOME,X-Synthesis-Ref0:;;111 One Street;London;;W1 1AA;
BDAY:20081008
PHOTO;TYPE=JPEG;ENCODING=BASE64:wolQTkcNChoKAAAADUlIRFIAAAAJAAAACQIDAAAAwp3
 Dv8OuwoMAAAAJUExURcK6ABZmZmYAAADCjMK1w4xCAAAAAXRSTlMAQMOmw5hmAAAAGklEQVQIW2
 NgAMKBBgYmRgEIZmHCgWABRjAGAQAVZgDDoQfDtsKjw7wAAAAASUVORMKuQmDCgg==
END:VCARD
';

        $hash = $this->toHash($vcard, self::$emailMap);
        $this->assertEquals(136, strlen($hash['photo']));

        unset($hash['photo']);
        $this->assertEquals(
            array(
                'lastname' => 'McTester',
                'firstname' => 'Testie',
                'name' => 'Testie McTester',
                'company' => 'Testers Inc',
                'department' => '',
                'cellPhone' => '+44 775550555',
                'homePhone' => '+44 205550555',
                'workPhone' => '+44 205550556',
                'homeEmail' => 'test@example.org',
                'emails' => 'test@example.org,test@example.com',
                'workEmail' => 'test@example.com',
                'homeAddress' => '111 One Street
London W1 1AA',
                'homeStreet' => '111 One Street',
                'homeCity' => 'London',
                'homePostalCode' => 'W1 1AA',
                'commonAddress' => '111 One Street
London W1 1AA',
                'commonStreet' => '111 One Street',
                'commonCity' => 'London',
                'commonPostalCode' => 'W1 1AA',
                'birthday' => '2008-10-08',
                'phototype' => 'JPEG'
            ),
            $hash
        );
    }

    protected function toHash($vcard, $map = array())
    {
        $driver = new Turba_Driver();
        foreach ($map as $field => $config) {
            $driver->map[$field] = $config;
        }
        $ical = new Horde_Icalendar();
        $ical->parsevCalendar($vcard);
        return $driver->toHash($ical->getComponent(0));
    }

    public function  testBug14046()
    {
        $vard = 'BEGIN:VCARD
VERSION:3.0
UID:20110107095409.cA7RPZcRtLVNJykRD60mE0A@h4.theupstairsroom.com
FN:Michael Joseph Rubinsky
NICKNAME:Mike
X-EPOCSECONDNAME:Mike
TZ;VALUE=text:America/New_York
EMAIL;TYPE=WORK:mrubinsk@horde.org
N:Rubinsky;Michael;Joseph;;
END:VCARD';

    $hash = $this->toHash($vcard, self::$emailMap);
    $this->assertEquals($hash['workEmail'] = 'mrubinsk@horde.org');
    $this->assertEmpty($hash['email']);
    }

}
