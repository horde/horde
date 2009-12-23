<?php
/**
 * Test the incoming filter class within the Kolab filter implementation.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/IncomingTest.php,v 1.8 2009/03/20 23:41:40 wrobel Exp $
 *
 * @package Horde_Kolab_Filter
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Filter.php';

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Extensions/OutputTestCase.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Filter/Incoming.php';

/**
 * Test the incoming filter.
 *
 * $Horde: framework/Kolab_Filter/test/Horde/Kolab/Filter/IncomingTest.php,v 1.8 2009/03/20 23:41:40 wrobel Exp $
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Kolab_Filter
 */
class Horde_Kolab_Filter_IncomingTest extends Horde_Kolab_Test_Filter
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        global $conf;

        $conf = array();

        $test = new Horde_Kolab_Test_Filter();
        $test->prepareBasicSetup();

        $conf['log']['enabled']          = false;

        $conf['kolab']['filter']['debug'] = true;

        $conf['kolab']['imap']['server'] = 'localhost';
        $conf['kolab']['imap']['port']   = 0;

        $_SERVER['SERVER_NAME'] = 'localhost';
    }


    /**
     * Test receiving the simple.eml message.
     */
    public function testSimpleIn()
    {
        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/simple.eml',
                           dirname(__FILE__) . '/fixtures/simple2.ret',
                           '', '', 'wrobel@example.org', 'me@example.org',
                           'home.example.org', $params);
    }

    /**
     * Test handling the line end with incoming messages.
     */
    public function testIncomingLineEnd()
    {
        $params = array('unmodified_content' => true,
                        'incoming' => true);

        $this->sendFixture(dirname(__FILE__) . '/fixtures/empty.eml',
                           dirname(__FILE__) . '/fixtures/empty2.ret',
                           '', '127.0.0.1', 'wrobel@example.org', 'me@example.org',
                           'home.example.org', $params);
    }
}
