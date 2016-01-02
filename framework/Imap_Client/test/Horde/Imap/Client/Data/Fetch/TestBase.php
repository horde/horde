<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Base class for testing the Horde_Imap_Client_Data_Fetch object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
abstract class Horde_Imap_Client_Data_Fetch_TestBase
extends PHPUnit_Framework_TestCase
{
    private $ob;

    /* Set in child class via _setUp(). */
    protected $ob_class;
    abstract protected function _setUp();

    public function setUp()
    {
        $this->_setUp();

        $this->ob = new Horde_Imap_Client_Data_Fetch($this->ob_class);
    }

    /**
     * @dataProvider fullMsgProvider
     */
    public function testFullMsg($stream_ob, $set_stream)
    {
        $string = strval($stream_ob);
        $stream_ob->rewind();

        $this->ob->setFullMsg($set_stream ? $stream_ob->stream : $string);
        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $string,
                $val->getFullMsg(false)
            );
            $this->assertEquals(
                $string,
                stream_get_contents($val->getFullMsg(true))
            );
        }
    }

    public function fullMsgProvider()
    {
        $stream = new Horde_Stream_String(array('string' => 'Foo'));

        return array(
            array(
                clone $stream,
                false
            ),
            array(
                clone $stream,
                true
            )
        );
    }

    public function testStructure()
    {
        $this->assertInstanceOf(
            'Horde_Mime_Part',
            $this->ob->getStructure()
        );

        $test = new Horde_Mime_Part();
        $test->setType('image/foo');

        $this->ob->setStructure($test);
        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $ret = $val->getStructure();

            $this->assertInstanceOf(
                'Horde_Mime_Part',
                $ret
            );
            $this->assertEquals(
                'image/foo',
                $ret->getType('image/foo')
            );
        }
    }

    /**
     * @dataProvider headersProvider
     */
    public function testHeaders($input, $string)
    {
        $label = 'foo';

        if (!is_null($input)) {
            $this->ob->setHeaders($label, $input);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $string,
                $val->getHeaders($label)
            );

            $stream = $val->getHeaders(
                $label,
                constant($this->ob_class . '::HEADER_STREAM')
            );
            rewind($stream);

            $this->assertEquals(
                $string,
                stream_get_contents($stream)
            );

            $hdr_ob = $val->getHeaders(
                $label,
                constant($this->ob_class . '::HEADER_PARSE')
            );

            $this->assertInstanceOf(
                'Horde_Mime_Headers',
                $hdr_ob
            );
            $this->assertEquals(
                trim($string),
                trim($hdr_ob->toString(array('nowrap' => true)))
            );
        }
    }

    public function headersProvider()
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('From', 'test@example.com');

        return array(
            array(
                null,
                ''
            ),
            array(
                "From: test@example.com\n\n",
                "From: test@example.com\n\n"
            ),
            array(
                $hdrs,
                "From: test@example.com\n\n"
            )
        );
    }

    /**
     * @dataProvider headerTextProvider
     */
    public function testHeaderText($input, $string)
    {
        $id = 1;

        if (!is_null($input)) {
            $this->ob->setHeaderText($id, $input);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $string,
                $val->getHeaderText($id)
            );

            $stream = $val->getHeaderText(
                $id,
                constant($this->ob_class . '::HEADER_STREAM')
            );
            rewind($stream);

            $this->assertEquals(
                $string,
                stream_get_contents($stream)
            );

            $hdr_ob = $val->getHeaderText(
                $id,
                constant($this->ob_class . '::HEADER_PARSE')
            );

            $this->assertInstanceOf(
                'Horde_Mime_Headers',
                $hdr_ob
            );
            $this->assertEquals(
                trim($string),
                trim($hdr_ob->toString(array('nowrap' => true)))
            );
        }
    }

    public function headerTextProvider()
    {
        return array(
            array(
                null,
                ''
            ),
            array(
                "From: test@example.com\n\n",
                "From: test@example.com\n\n"
            )
        );
    }

    /**
     * @dataProvider mimeHeaderProvider
     */
    public function testMimeHeader($input, $string)
    {
        $id = 1;

        if (!is_null($input)) {
            $this->ob->setMimeHeader($id, $input);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $string,
                $val->getMimeHeader($id)
            );

            $stream = $val->getMimeHeader(
                $id,
                constant($this->ob_class . '::HEADER_STREAM')
            );
            rewind($stream);

            $this->assertEquals(
                $string,
                stream_get_contents($stream)
            );

            $hdr_ob = $val->getMimeHeader(
                $id,
                constant($this->ob_class . '::HEADER_PARSE')
            );

            $this->assertInstanceOf(
                'Horde_Mime_Headers',
                $hdr_ob
            );
            $this->assertEquals(
                trim($string),
                trim($hdr_ob->toString(array('nowrap' => true)))
            );
        }
    }

    public function mimeHeaderProvider()
    {
        return array(
            array(
                null,
                ''
            ),
            array(
                "From: test@example.com\n\n",
                "From: test@example.com\n\n"
            )
        );
    }

    /**
     * @dataProvider bodyPartProvider
     */
    public function testBodyPart($stream_ob, $set_stream, $decode)
    {
        $id = 1;
        $string = strval($stream_ob);
        $stream_ob->rewind();

        if (strlen($string)) {
            $this->ob->setBodyPart(
                $id,
                $set_stream ? $stream_ob->stream : $string,
                $decode
            );
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $string,
                $val->getBodyPart($id, false)
            );
            $this->assertEquals(
                $string,
                stream_get_contents($val->getBodyPart($id, true))
            );
            $this->assertEquals(
                $decode,
                $this->ob->getBodyPartDecode($id)
            );
        }
    }

    public function bodyPartProvider()
    {
        $stream = new Horde_Stream_String(array('string' => 'Foo'));
        $empty_stream = new Horde_Stream();

        return array(
            array(
                clone $stream,
                false,
                null
            ),
            array(
                clone $stream,
                false,
                '8bit'
            ),
            array(
                clone $stream,
                false,
                'binary'
            ),
            array(
                clone $empty_stream,
                false,
                null
            ),
            array(
                clone $stream,
                true,
                null
            ),
            array(
                clone $stream,
                true,
                '8bit'
            ),
            array(
                clone $stream,
                true,
                'binary'
            ),
            array(
                clone $empty_stream,
                true,
                null
            )
        );
    }

    /**
     * @dataProvider bodyPartSizeProvider
     */
    public function testBodyPartSize($size)
    {
        $id = 1;

        if (!is_null($size)) {
            $this->ob->setBodyPartSize($id, $size);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $size,
                $val->getBodyPartSize($id)
            );
        }

    }

    public function bodyPartSizeProvider()
    {
        return array(
            array(200),
            array(null)
        );
    }

    /**
     * @dataProvider bodyTextProvider
     */
    public function testBodyText($stream_ob, $set_stream)
    {
        $id = 1;
        $string = strval($stream_ob);
        $stream_ob->rewind();

        if (strlen($string)) {
            $this->ob->setBodyText(
                $id,
                $set_stream ? $stream_ob->stream : $string
            );
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $string,
                $val->getBodyText($id, false)
            );
            $this->assertEquals(
                $string,
                stream_get_contents($val->getBodyText($id, true))
            );
        }
    }

    public function bodyTextProvider()
    {
        $stream = new Horde_Stream_String(array('string' => 'Foo'));

        return array(
            array(
                clone $stream,
                false
            ),
            array(
                clone $stream,
                true
            )
        );
    }

    /**
     * @dataProvider envelopeProvider
     */
    public function testEnvelope($data, $to)
    {
        if (!is_null($data)) {
            $this->ob->setEnvelope($data);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $envelope = $val->getEnvelope();

            $this->assertInstanceOf(
                'Horde_Imap_Client_Data_Envelope',
                $envelope
            );

            $addr_ob = $envelope->to;

            $this->assertInstanceof(
                'Horde_Mail_Rfc822_List',
                $addr_ob
            );
            $this->assertEquals(
                $to,
                strval($addr_ob)
            );
        }
    }

    public function envelopeProvider()
    {
        $addr = new Horde_Imap_Client_Data_Envelope();
        $addr->to = 'foo@example.com';

        return array(
            array(
                array('to' => 'foo@example.com'),
                'foo@example.com'
            ),
            array(
                $addr,
                'foo@example.com'
            ),
            array(
                null,
                ''
            )
        );
    }

    /**
     * @dataProvider flagsProvider
     */
    public function testFlags($flags, $expected)
    {
        $this->ob->setFlags($flags);
        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $f = $this->ob->getFlags();

            $this->assertEquals(
                $expected,
                $f
            );
        }
    }

    public function flagsProvider()
    {
        return array(
            array(
                array('foo'),
                array('foo')
            ),
            array(
                array(),
                array(),
            ),
            array(
                array('FoO', 'BAR', '     baZ  '),
                array('foo', 'bar', 'baz')
            )
        );
    }

    /**
     * @dataProvider imapDateProvider
     */
    public function testImapDate($date, $expected)
    {
        if (!is_null($date)) {
            $this->ob->setImapDate($date);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $d = $this->ob->getImapDate();

            $this->assertInstanceOf(
                'Horde_Imap_Client_DateTime',
                $d
            );

            $this->assertEquals(
                is_null($expected) ? time() : $expected,
                intval(strval($d))
            );
        }
    }

    public function imapDateProvider()
    {
        return array(
            array(
                '12 Sep 2007 15:49:12 UT',
                1189612152
            ),
            array(
                new Horde_Imap_Client_DateTime('12 Sep 2007 15:49:12 UT'),
                1189612152
            ),
            array(
                null,
                null
            )
        );
    }

    /**
     * @dataProvider sizeProvider
     */
    public function testSize($size, $expected)
    {
        if (!is_null($size)) {
            $this->ob->setSize($size);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $expected,
                $this->ob->getSize()
            );
        }
    }

    public function sizeProvider()
    {
        return array(
            array(
                200,
                200
            ),
            array(
                null,
                0
            )
        );
    }

    /**
     * @dataProvider uidProvider
     */
    public function testUid($uid, $expected)
    {
        if (!is_null($uid)) {
            $this->ob->setUid($uid);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $expected,
                $this->ob->getUid()
            );
        }
    }

    public function uidProvider()
    {
        return array(
            array(
                200,
                200
            ),
            array(
                null,
                null
            )
        );
    }

    /**
     * @dataProvider seqProvider
     */
    public function testSeq($seq, $expected)
    {
        if (!is_null($seq)) {
            $this->ob->setSeq($seq);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $expected,
                $this->ob->getSeq()
            );
        }
    }

    public function seqProvider()
    {
        return array(
            array(
                200,
                200
            ),
            array(
                null,
                null
            )
        );
    }

    /**
     * @dataProvider modSeqProvider
     */
    public function testModSeq($modseq, $expected)
    {
        if (!is_null($modseq)) {
            $this->ob->setModSeq($modseq);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $expected,
                $this->ob->getModSeq()
            );
        }
    }

    public function modSeqProvider()
    {
        return array(
            array(
                200,
                200
            ),
            array(
                null,
                null
            )
        );
    }

    /**
     * @dataProvider downgradedProvider
     */
    public function testDowngraded($downgraded, $expected)
    {
        if (!is_null($downgraded)) {
            $this->ob->setDowngraded($downgraded);
        }

        $serialize_ob = unserialize(serialize($this->ob));

        foreach (array($this->ob, $serialize_ob) as $val) {
            $this->assertEquals(
                $expected,
                $this->ob->isDowngraded()
            );
        }
    }

    public function downgradedProvider()
    {
        return array(
            array(
                true,
                true
            ),
            array(
                false,
                false
            ),
            array(
                null,
                false
            )
        );
    }

    public function testMerge()
    {
        $this->ob->setUid(1);

        $this->assertEquals(
            1,
            $this->ob->getUid()
        );
        $this->assertNull($this->ob->getSeq());

        $ob2 = new Horde_Imap_Client_Data_Fetch($this->ob_class);
        $ob2->setUid(2);
        $ob2->setSeq(2);

        $this->ob->merge($ob2);

        $this->assertEquals(
            2,
            $this->ob->getUid()
        );
        $this->assertEquals(
            2,
            $this->ob->getSeq()
        );
    }

    public function testObjectStateFunctions()
    {
        $this->assertEmpty($this->ob->getRawData());
        $this->assertFalse($this->ob->exists(Horde_Imap_Client::FETCH_UID));
        $this->assertFalse($this->ob->exists(Horde_Imap_Client::FETCH_SEQ));
        $this->assertTrue($this->ob->isDefault());

        $this->ob->setUid(1);

        $this->assertNotEmpty($this->ob->getRawData());
        $this->assertTrue($this->ob->exists(Horde_Imap_Client::FETCH_UID));
        $this->assertFalse($this->ob->exists(Horde_Imap_Client::FETCH_SEQ));
        $this->assertFalse($this->ob->isDefault());
    }

    public function testClone()
    {
        $stream = new Horde_Stream_String(array('string' => 'Foo'));
        $stream->rewind();

        $this->ob->setFullMsg($stream->stream);

        $ob2 = clone $this->ob;

        $this->ob->setFullMsg('Bar');

        $this->assertEquals(
            'Bar',
            $this->ob->getFullMsg()
        );
        $this->assertEquals(
            'Foo',
            $ob2->getFullMsg()
        );
    }

}
