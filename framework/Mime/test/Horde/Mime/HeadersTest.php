<?php
/**
 * Tests for the Horde_Mime_Headers class.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_HeadersTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('Subject', 'My Subject');
        $hdrs->addHeader('To', 'recipient@example.com');
        $hdrs->addHeader('Cc', 'null@example.com');
        $hdrs->addHeader('Bcc', 'invisible@example.com');
        $hdrs->addHeader('From', 'sender@example.com');

        $stored = serialize($hdrs);
        $hdrs2 = unserialize($stored);

        $this->assertEquals(
            'null@example.com',
            $hdrs2->getValue('cc')
        );
    }

}
