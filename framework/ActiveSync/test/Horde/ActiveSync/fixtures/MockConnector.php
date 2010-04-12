<?php
/**
 * Mock connector for unit testing horde backend.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_MockConnector
{
    public function __construct($params = array())
    {
        $this->_fixture = $params['fixture'];
    }

    public function __call($name, $args)
    {
        if (empty($this->_fixture[$name])) {
            return 0;
        }
        return $this->_fixture[$name];
    }

    public function contacts_import($content)
    {
        // $content is a vCard that should eq:
        $expected = array(
            'firstname' => 'Michael',
            'lastname' => 'Rubinsky',
            'middlenames' => 'Joseph',
            'namePrefix' => '',
            'nameSuffix' => '',
            'name' => 'Michael Joseph Rubinsky',
            'birthday' => '1970-03-20',
            'homeStreet' => '123 Main St.',
            'homeCity' => 'Anywhere',
            'homeProvince' => 'NJ',
            'homePostalCode' => '08080',
            'homeCountry' => '',
            'workStreet' => '',
            'workCity' => '',
            'workProvince' => '',
            'workCountry' => '',
            //'timezone' => '',
            'email' => 'mrubinsk@horde.org',
            'homePhone' => '(856)555-1234',
            'workPhone' => '(856)555-5678',
            'cellPhone' => '(609)555-9876',
            'fax' => '',
            'pager' => '',
            'title' => '',
            'company' => '',
            //'category' => '',
            'notes' => '',
            'website' => '',
        );

        foreach ($expected as $key => $value) {
            if ($content[$key] != $value) {
                throw new Horde_ActiveSync_Exception('Expected value ' . $value . ' did not match received value ' . $content[$key]);
            }
        }

        return 'xx.xx@localhost';
    }

    public function contacts_replace()
    {
        
    }

    public function calendar_import()
    {

    }
}