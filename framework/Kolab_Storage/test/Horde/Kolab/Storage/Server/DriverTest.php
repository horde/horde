<?php
/**
 * Server test of the different driver implementations.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Server test of the different driver implementations.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Server_DriverTest extends PHPUnit_Framework_TestCase
{
    const MOCK         = 'Mock';
    const CCLIENT      = 'Cclient';
    const PEAR         = 'Pear';
    const IMAP_SOCKET  = 'Imap_Socket';
    const IMAP_CCLIENT = 'Imap_Cclient';

    public function setUp()
    {
        if ($this->sharedFixture === null) {
            $this->markTestSkipped('Testing of a running server skipped. No configuration fixture available.');
            return;
        }

        /** @todo: FIXME -> required for namespace handling */
        $GLOBALS['registry']->setCharset('UTF8');

        /** Setup group handler */
        require_once 'Horde/Group.php';
        require_once 'Horde/Group/mock.php';
        $this->group = new Group_mock();


    }

    public function tearDown()
    {
        /** Reactivate strict reporting as we need to turn it off for PEAR-Net_IMAP */
        if (!empty($this->old_error_reporting)) {
            error_reporting($this->old_error_reporting);
        }
    }

    public function provideDrivers()
    {
        return array(
            'mock driver' => array(self::MOCK),
            'PHP c-client based driver' => array(self::CCLIENT),
            'PEAR-Net_IMAP based driver' => array(self::PEAR),
            'Horde_Imap_Client_Socket based driver' => array(self::IMAP_SOCKET),
            'Horde_Imap_Client_Cclient based driver' => array(self::IMAP_CCLIENT),
        );
    }

    private function _getDriver($driver)
    {
        if ($driver == self::PEAR) {
            /** PEAR-Net_IMAP is not E_STRICT */
            $this->old_error_reporting = error_reporting(E_ALL & ~E_STRICT);
        }
        if (!isset($this->sharedFixture->drivers[$driver])) {
            switch ($driver) {
            case self::MOCK:
                $connection = new Horde_Kolab_Storage_Driver_Mock($this->group);
                break;
            case self::CCLIENT:
                $connection = new Horde_Kolab_Storage_Driver_Cclient(
                    $this->group
                );
                break;
            case self::PEAR:
                $client = new Net_IMAP($this->sharedFixture->conf['host'], 143, false);
                $client->login(
                    $this->sharedFixture->conf['user'],
                    $this->sharedFixture->conf['pass']
                );

                $connection = new Horde_Kolab_Storage_Driver_Pear(
                    $client,
                    $this->group
                );
                break;
            case self::IMAP_SOCKET:
                $params = array(
                    'hostspec' => $this->sharedFixture->conf['host'],
                    'username' => $this->sharedFixture->conf['user'],
                    'password' => $this->sharedFixture->conf['pass'],
                    'debug'    => $this->sharedFixture->conf['debug'],
                    'port'     => 143,
                    'secure'   => false
                );
                $client = Horde_Imap_Client::factory('socket', $params);
                $client->login();

                $connection = new Horde_Kolab_Storage_Driver_Imap(
                    $client,
                    $this->group
                );
                break;
            case self::IMAP_CCLIENT:
                $params = array(
                    'hostspec' => $this->sharedFixture->conf['host'],
                    'username' => $this->sharedFixture->conf['user'],
                    'password' => $this->sharedFixture->conf['pass'],
                    'debug'    => $this->sharedFixture->conf['debug'],
                    'port'     => 143,
                    'secure'   => false
                );
                $client = Horde_Imap_Client::factory('cclient', $params);
                $client->login();

                $connection = new Horde_Kolab_Storage_Driver_Imap(
                    $client,
                    $this->group
                );
                break;
            default:
                exit("Undefined storage driver!\n");
            }
            $this->sharedFixture->drivers[$driver] = $connection;
        }
        return $this->sharedFixture->drivers[$driver];
    }

    /**
     * @dataProvider provideDrivers
     */
    public function testDriverType($driver)
    {
        $this->assertType('Horde_Kolab_Storage_Driver', $this->_getDriver($driver));
    }

    /**
     * @dataProvider provideDrivers
     */
    public function testGetNamespace($driver)
    {
        $namespaces = array();
        foreach ($this->_getDriver($driver)->getNamespace() as $namespace) {
            $namespaces[$namespace->getName()] = array(
                'type' => $namespace->getType(),
                'delimiter' => $namespace->getDelimiter(),
            );
        }
        $this->assertEquals(
            array(
                'INBOX' => array(
                    'type' => 'personal',
                    'delimiter' => '/',
                ),
                'user' => array(
                    'type' => 'other',
                    'delimiter' => '/',
                ),
                '' => array(
                    'type' => 'shared',
                    'delimiter' => '/',
                ),
            ),
            $namespaces
        );
    }
}