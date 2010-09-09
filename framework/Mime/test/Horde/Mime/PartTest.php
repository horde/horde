<?php
/**
 * Tests for the Horde_Mime_Part class.
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
class Horde_Mime_PartTest extends PHPUnit_Framework_TestCase
{
    public function testParseMessage()
    {
        $msg = file_get_contents(dirname(__FILE__) . '/fixtures/sample_msg.txt');
        $part = Horde_Mime_Part::parseMessage($msg);

        $this->assertEquals(
            'multipart/mixed',
            $part->getType()
        );
        $this->assertEquals(
            1869,
            $part->getBytes()
        );
        $this->assertEquals(
            '=_k4kgcwkwggwc',
            $part->getContentTypeParameter('boundary')
        );

        $part_1 = $part->getPart(1);
        $this->assertEquals(
            'text/plain',
            $part_1->getType()
        );
        $this->assertEquals(
            'flowed',
            $part_1->getContentTypeParameter('format')
        );

        $part_2 = $part->getPart(2);
        $this->assertEquals(
            'message/rfc822',
            $part_2->getType()
        );
        $part_2_2 = $part->getPart(2.2);
        $this->assertEquals(
            'text/plain',
            $part_2_2->getType()
        );
        $this->assertEquals(
            'test.txt',
            $part_2_2->getDispositionParameter('filename')
        );

        $part_3 = $part->getPart(3);
        $this->assertEquals(
            'image/png',
            $part_3->getType()
        );

        $this->assertEquals(
            "Test text.\n\n",
            Horde_Mime_Part::getRawPartText($msg, 'body', '2.1')
        );

        $this->assertEquals(
            "Content-Type: image/png; name=index.png\n" .
            "Content-Disposition: attachment; filename=index.png\n" .
            'Content-Transfer-Encoding: base64',
            Horde_Mime_Part::getRawPartText($msg, 'header', '3')
        );
    }

}
