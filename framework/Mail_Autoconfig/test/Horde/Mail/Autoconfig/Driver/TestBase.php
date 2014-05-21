<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mail_Autoconfig
 * @subpackage UnitTests
 */

/**
 * Base driver for Tests for the SRV Driver.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mail_Autoconfig
 * @subpackage UnitTests
 */
abstract class Horde_Mail_Autoconfig_Driver_TestBase
extends Horde_Test_Case
{
    private $driver;

    public function setUp()
    {
        $this->driver = $this->_getDriver();
    }

    abstract protected function _getDriver();

    public function tearDown()
    {
        unset($this->driver);
    }

    /**
     * @dataProvider provider
     */
    public function testGetMsaConfig($domains)
    {
        if (!$domains) {
            $this->markTestSkipped();
        }

        $res = $this->driver->msaSearch($domains, array(
            'email' => new Horde_Mail_Rfc822_Address('test@example.com')
        ));

        $this->assertNotFalse($res);
        $this->assertNotEmpty($res);
        foreach ($res as $val) {
            $this->assertInstanceOf('Horde_Mail_Autoconfig_Server_Msa', $val);
        }
    }

    /**
     * @dataProvider provider
     */
    public function testGetMailConfig($domains)
    {
        if (!$domains) {
            $this->markTestSkipped();
        }

        $res = $this->driver->mailSearch($domains, array(
            'email' => new Horde_Mail_Rfc822_Address('test@example.com')
        ));

        $this->assertNotFalse($res);
        $this->assertNotEmpty($res);
        foreach ($res as $val) {
            $this->assertInstanceOf('Horde_Mail_Autoconfig_Server', $val);
        }
    }

    public function provider()
    {
        $config = self::getConfig('MAILAUTOCONFIG_TEST_CONFIG', __DIR__ . '/..');
        if (!is_null($config) &&
            !empty($config['mail_autoconfig']['domains'])) {
            return $config['mail_autoconfig']['domains'];
        }

        return array(array(null));
    }

}
