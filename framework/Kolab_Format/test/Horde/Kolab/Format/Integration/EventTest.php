<?php
/**
 * Test event handling within the Kolab format implementation.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test event handling.
 *
 * Copyright 2007-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Integration_EventTest
extends Horde_Kolab_Format_TestCase
{
    /**
     * Test for https://www.intevation.de/roundup/kolab/issue3525
     *
     * @return NULL
     */
    public function testIssue3525()
    {
        $xml = $this->getFactory()->create('XML', 'event');

        // Load XML
        $event  = file_get_contents(
            __DIR__ . '/../fixtures/event_umlaut.xml'
        );
        $result = $xml->load($event);

        // Check that the xml loads fine
        $this->assertEquals('...übbe...', $result['body']);

        $xml = $this->getFactory()->create('XML', 'event');

        // Load XML
        $event  = file_get_contents(
            __DIR__ . '/../fixtures/event_umlaut_broken.xml'
        );
        $result = $xml->load($event);

        $this->assertEquals('...übbe...', $result['body']);
    }
}
