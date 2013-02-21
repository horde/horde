<?php
/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */

/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */
class Horde_Service_Gravatar_ServerTest extends Horde_Test_Case
{
    private $_server;

    public function setUp()
    {
        $config = self::getConfig('SERVICE_GRAVATAR_TEST_CONFIG');
        if ($config && !empty($config['service']['gravatar']['server'])) {
            $this->_server = $config['service']['gravatar']['server'];
        } else {
            $this->markTestSkipped('Configuration is missing and remote server tests are disabled.');
        }
    }

    public function testGetProfile()
    {
        $g = new Horde_Service_Gravatar($this->_server);
        $profile = $g->getProfile('wrobel@horde.org');
        $this->assertEquals(
            'http://gravatar.com/abc1xyz2',
            $profile['entry'][0]['profileUrl']
        );
    }
}
