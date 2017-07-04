<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/apache Apache-like
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 */
class Turba_Unit_LdifTest extends Turba_TestCase
{
    /**
     * @dataProvider exportFiles
     */
    public function testExport($contact, $ldif)
    {
        $data = new Turba_Data_Ldif(new Horde_Data_Storage_Mock());
        $this->assertStringEqualsFile($ldif, $data->exportData($contact, false));
    }

    /**
     * @dataProvider importFiles
     */
    public function testImport($contact, $ldif)
    {
        $data = new Turba_Data_Ldif(new Horde_Data_Storage_Mock());
        $this->assertEquals($contact, $data->importFile($ldif, false));
    }

    public function exportFiles()
    {
        return array(
            array(
                array(array(
                    'firstname' => 'John',
                    'lastname' => 'Püblic',
                    'name' => 'John Püblic',
                    'email' => 'john@example.com'
                )),
                __DIR__ . '/../fixtures/bug_6518.ldif',
            ),
            array(
                array(
                    array(
                        'firstname' => 'John',
                        'lastname' => 'Smith',
                        'name' => 'John Smith',
                        'email' => 'js23@school.edu'),
                    array(
                        'firstname' => 'Charles',
                        'lastname' => 'Brown',
                        'name' => 'Charlie Brown',
                        'alias' => 'Chuck',
                        'birthday' => 'May 1',
                        'workPhone' => '+1 212 876 5432',
                        'homePhone' => '+1 203 234 5678',
                        'fax' => '+1 203 999 9999',
                        'cellPhone' => '+1 917 321 0987',
                        'homeStreet' => '12 west 57 street',
                        'homeCity' => 'New York',
                        'homeProvince' => 'New York',
                        'homePostalCode' => '10001',
                        'homeCountry' => 'USA',
                        'workStreet' => '12 west 55 street',
                        'workCity' => 'New York',
                        'workProvince' => 'New York',
                        'workPostalCode' => '10001',
                        'workCountry' => 'USA',
                        'title' => 'Senior Systems Programmer',
                        'department' => 'SUIT',
                        'company' => 'School University',
                        'website' => 'http://www.school.edu/',
                        'freebusyUrl' => 'http://www.school.edu/~chuck/fb.ics',
                        'notes' => 'hi mom
',
                        'email' => 'brown@school.edu'
                    ),
                ),
                __DIR__ . '/../fixtures/export.ldif',
            ),
        );
    }

    public function importFiles()
    {
        $result = array(
            array(
                'givenName' => 'John',
                'sn' => 'Smith',
                'cn' => 'John Smith',
                'mail' => 'js23@school.edu',
            ),
            array(
                'givenName' => 'Charles',
                'sn' => 'Brown',
                'cn' => 'Charlie Brown',
                'mozillaNickname' => 'Chuck',
                'mail' => 'brown@school.edu',
                'telephoneNumber' => '+1 212 876 5432',
                'homePhone' => '+1 203 234 5678',
                'fax' => '+1 203 999 9999',
                'mobile' => '+1 917 321 0987',
                'homeStreet' => '12 west 57 street',
                'mozillaHomeStreet2' => 'Apt 2076',
                'mozillaHomeLocalityName' => 'New York',
                'mozillaHomeState' => 'New York',
                'mozillaHomePostalCode' => '10001',
                'mozillaHomeCountryName' => 'USA',
                'street' => '12 West 55th Street',
                'mozillaWorkStreet2' => 'Room 22',
                'l' => 'New York',
                'st' => 'New York',
                'postalCode' => '10001',
                'c' => 'USA',
                'title' => 'Senior Systems Programmer',
                'department' => 'CUIT',
                'company' => 'School University',
                'mozillaWorkUrl' => 'http://www.school.edu/',
                'description' => 'hi mom
',
                'mozillaSecondEmail' => 'brown@gmail.com',
            ),
        );

        return array(
            array(
                $result,
                __DIR__ . '/../fixtures/import.ldif',
            ),
            array(
                $result,
                __DIR__ . '/../fixtures/importCRLF.ldif',
            ),
        );
    }
}
