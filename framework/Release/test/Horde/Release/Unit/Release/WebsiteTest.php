<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
 */

/**
 * Test the website udpate.
 *
 * @category   Horde
 * @package    Release
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Release
 */
class Horde_Release_Unit_Release_WebsiteTest extends Horde_Release_TestCase
{
    public function setUp()
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->query('CREATE TABLE versions (application VARCHAR(255), state VARCHAR(255), version VARCHAR(255), date VARCHAR(10), pear BOOLEAN, dir VARCHAR(255), PRIMARY KEY (application, state))');
    }

    /**
     * @expectedException LogicException
     */
    public function testFailWithMissingApplication()
    {
        $this->_createObject()->addNewVersion(array(
            'version' => '1.0.1'
        ));
    }

    /**
     * @expectedException LogicException
     */
    public function testFailWithMissingVersion()
    {
        $this->_createObject()->addNewVersion(array(
            'application' => 'horde',
        ));
    }

    /**
     * @expectedException LogicException
     */
    public function testFailWithIncorrectState()
    {
        $this->_createObject()->addNewVersion(array(
            'application' => 'horde',
            'state' => 'foo',
            'version' => '1.0.1'
        ));
    }

    public function testAddNewVersion()
    {
        $this->_createObject()->addNewVersion(array(
            'application' => 'horde',
            'state' => 'stable',
            'version' => '1.0.1'
        ));
        $this->assertEquals(
            '1.0.1',
            $this->db
                ->query('SELECT version FROM versions WHERE application = \'horde\' AND state = \'stable\'')
                ->fetchColumn()
        );
    }

    public function testUpdateNewVersion()
    {
        $this->_createObject()->addNewVersion(array(
            'application' => 'horde',
            'state' => 'dev',
            'version' => '1.0.0RC1'
        ));
        $this->_createObject()->addNewVersion(array(
            'application' => 'horde',
            'state' => 'dev',
            'version' => '1.0.0RC2'
        ));
        $this->assertEquals(
            1,
            $this->db
                ->query('SELECT count(*) FROM versions WHERE application = \'horde\' AND state = \'dev\'')
                ->fetchColumn()
        );
        $this->assertEquals(
            '1.0.0RC2',
            $this->db
                ->query('SELECT version FROM versions WHERE application = \'horde\' AND state = \'dev\'')
                ->fetchColumn()
        );
    }

    public function testDetectState()
    {
        $this->_createObject()->addNewVersion(array(
            'application' => 'foo',
            'version' => '1.0.0beta1'
        ));
        $this->_createObject()->addNewVersion(array(
            'application' => 'foo',
            'version' => '1.0.1'
        ));
        $this->assertEquals(
            array(
                array(
                    0 => '1.0.0beta1',
                    'version' => '1.0.0beta1',
                    1 => 'dev',
                    'state' => 'dev',
                ),
                array(
                    0 => '1.0.1',
                    'version' => '1.0.1',
                    1 => 'stable',
                    'state' => 'stable',
                ),
            ),
            $this->db
                ->query('SELECT version, state FROM versions WHERE application = \'foo\'')
                ->fetchAll()
        );
    }

    public function testNow()
    {
        $now = new DateTime('now');
        $this->_createObject()->addNewVersion(array(
            'application' => 'bar',
            'state' => 'stable',
            'version' => '1.0.1'
        ));
        $this->assertEquals(
            $now->format('Y-m-d'),
            $this->db
                ->query('SELECT date FROM versions WHERE application = \'bar\' AND state = \'stable\'')
                ->fetchColumn()
        );
    }

    protected function _createObject()
    {
        return new Horde_Release_Website($this->db);
    }
}
