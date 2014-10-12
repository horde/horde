<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Smtp
 * @subpackage UnitTests
 */

/**
 * Package testing on a remote SMTP server.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Smtp
 * @subpackage UnitTests
 */
class Horde_Smtp_RemoteServerTest extends Horde_Test_Case
{
    private $config;
    private $smtp;

    public function setUp()
    {
        $config = self::getConfig('SMTP_TEST_CONFIG');
        if (is_null($config)) {
            $this->markTestSkipped('Remote server not configured.');
        }
        $this->config = $config['smtp'];
    }

    public function tearDown()
    {
        if ($this->smtp) {
            unset($this->smtp);
        }
    }

    public function testLoginNoAuth()
    {
        unset($this->config['pass'], $this->config['user']);
        $this->_createSmtp();
        $this->smtp->login();
    }

    public function testLoginAuth()
    {
        if (!isset($this->config['pass']) || !isset($this->config['user'])) {
            $this->markTestSkipped('Authentication not configured.');
        }

        $this->_createSmtp();
        $this->smtp->login();
    }

    public function test8bitmime()
    {
        $this->_createSmtp();
        $this->assertEquals(
            $this->smtp->queryExtension('8BITMIME'),
            $this->smtp->data_8bit
        );
    }

    public function testBinaryMime()
    {
        $this->_createSmtp();
        $this->assertEquals(
            $this->smtp->queryExtension('BINARYMIME'),
            $this->smtp->data_binary
        );
    }

    public function testSize()
    {
        $this->_createSmtp();
        $this->assertEquals(
            $this->smtp->queryExtension('SIZE'),
            $this->smtp->size
        );
    }

    public function testNoop()
    {
        $this->_createSmtp();
        $this->smtp->noop();
    }

    public function testProcessQueue()
    {
        $this->_createSmtp();
        $this->smtp->processQueue();
    }

    protected function _createSmtp()
    {
        $this->smtp = new Horde_Smtp($this->config);
    }

}
