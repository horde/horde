<?php
/**
 * Test event handling within the Kolab format implementation.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/EventTest.php,v 1.1 2009/04/02 20:07:26 wrobel Exp $
 *
 * @package Kolab_Format
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test event handling.
 *
 * $Horde: framework/Kolab_Format/test/Horde/Kolab/Format/EventTest.php,v 1.1 2009/04/02 20:07:26 wrobel Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Format
 */
class Horde_Kolab_Format_EventTest extends PHPUnit_Framework_TestCase
{

    /**
     * Set up testing.
     */
    protected function setUp()
    {
        Horde_Nls::setCharset('utf-8');
    }


    /**
     * Test for https://www.intevation.de/roundup/kolab/issue3525
     */
    public function testIssue3525()
    {
        $xml = Horde_Kolab_Format::factory('XML', 'event');

        // Load XML
        $event = file_get_contents(dirname(__FILE__) . '/fixtures/event_umlaut.xml');
        $result = $xml->load($event);
        // Check that the xml loads fine
        $this->assertEquals(mb_convert_encoding($result['body'], 'UTF-8', 'ISO-8859-1'), '...übbe...');

        // Load XML
        $event = file_get_contents(dirname(__FILE__) . '/fixtures/event_umlaut_broken.xml');
        $result = $xml->load($event);
        //FIXME: Why does Kolab Format return ISO-8859-1? UTF-8 would seem more appropriate
        $this->assertEquals(mb_convert_encoding($result['body'], 'UTF-8', 'ISO-8859-1'), '...übbe...');
    }
}
