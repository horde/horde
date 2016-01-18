<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * Tests for Horde_Registry_Nlsconfig.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_NlsconfigTest extends Horde_Test_Case
{
    public function setUp()
    {
        $GLOBALS['session'] = new Horde_Session();
        $GLOBALS['session']->sessionHandler = new Horde_Support_Stub();
        $GLOBALS['registry'] = new Horde_Test_Stub_Registry('john', 'horde');
        $config = new Horde_Test_Stub_Registry_Loadconfig(
            'horde', 'nls.php', 'horde_nls_config'
        );
        foreach ($this->providerForTestGet() as $values) {
            $config->config['horde_nls_config'][$values[0]] = $values[1];
        }
        $GLOBALS['registry']->setConfigFile(
            $config, 'nls.php', 'horde_nls_config', 'horde'
        );
    }

    public function providerForTestGet()
    {
        return array(
            'languages' => array(
                'languages', array('en_US' => '&#x202d;English (American)')
            ),
            'aliases' => array(
                'aliases',
                array('ar' => 'ar_SY', 'bg' => 'bg_BG')
            ),
            'charsets' => array(
                'charsets',
                array('bg_BG' => 'windows-1251', 'bs_BA' => 'ISO-8859-2')
            ),
        );
    }

    /**
     * @dataProvider providerForTestGet
     */
    public function testGet($key, $expected)
    {
        $nls = new Horde_Registry_Nlsconfig();
    }

    public function testValidLang()
    {
        $nls = new Horde_Registry_Nlsconfig();
        $this->assertTrue($nls->validLang('en_US'));
        $this->assertFalse($nls->validLang('xy_XY'));
    }
}
