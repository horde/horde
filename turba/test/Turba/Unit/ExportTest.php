<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/apache Apache-like
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 */
class Turba_Unit_ExportTest extends Turba_TestCase
{
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
            '__tags' => 'Foo,Foo;Bar,Bar',
        );
        $this->driver = new Turba_Driver();
        $this->driver->map = array_fill_keys(array_diff(array_keys($this->contact), array('__tags')), true);
        $this->object = new Turba_Object($this->driver, $this->contact);
    }

    public function testExportVcard21()
    {
        $vcard = $this->driver->tovCard($this->object, '2.1');
        $this->assertStringEqualsFile(
            __DIR__ . '/../fixtures/export_21.vcf',
            $vcard->exportvCalendar());
    }

    public function testExportVcard30()
    {
        $vcard = $this->driver->tovCard($this->object, '3.0');
        $this->assertStringEqualsFile(
            __DIR__ . '/../fixtures/export_30.vcf',
            $vcard->exportvCalendar());
    }

    public function testExportBug9207()
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
        $this->assertStringEqualsFile(
            __DIR__ . '/../fixtures/bug_9207.vcf',
            $vcard->exportvCalendar());
    }
}
