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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */

require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */
class Horde_Service_Gravatar_ServerTest
extends PHPUnit_Framework_TestCase
{
    private $_server;

    public function setUp()
    {
        parent::setUp();
        if (@include_once(dirname(__FILE__) . '/conf.php')) {
            if (!empty($conf['service']['gravatar']['server'])) {
                $this->_server = $conf['service']['gravatar']['server'];
                return;
            }
        }
        $this->markTestSkipped('Configuration is missing and remote server tests are disabled.');
    }

    public function testGetProfile()
    {
        $g = new Horde_Service_Gravatar($this->_server);
        $profile = $g->getProfile('wrobel@horde.org');
        $this->assertEquals(
            'http://gravatar.com/gunnarwrobel',
            $profile['entry'][0]['profileUrl']
        );
    }
}