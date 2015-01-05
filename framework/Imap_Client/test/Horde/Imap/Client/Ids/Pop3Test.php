<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * POP3 specific tests for the Ids object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Ids_Pop3Test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider pop3SequenceStringGenerateProvider
     */
    public function testPop3SequenceStringGenerate($in, $expected)
    {
        $this->assertEquals(
            $expected,
            strval(new Horde_Imap_Client_Ids_Pop3($in))
        );
    }

    public function pop3SequenceStringGenerateProvider()
    {
        return array(
            array(array('ABCDEFGHIJ', 'ABCDE'), 'ABCDEFGHIJ ABCDE'),
            array('ABCDEFGHIJ', 'ABCDEFGHIJ')
        );
    }

    /**
     * @dataProvider pop3SequenceStringParseProvider
     */
    public function testPop3SequenceStringParse($in, $expected)
    {
        $ids = new Horde_Imap_Client_Ids_Pop3($in);
        $this->assertEquals(
            $expected,
            $ids->ids
        );
    }

    public function pop3SequenceStringParseProvider()
    {
        return array(
            array('ABCDEFGHIJ ABCDE', array('ABCDEFGHIJ', 'ABCDE')),
            array('ABCDEFGHIJ ABC ABCDE', array('ABCDEFGHIJ', 'ABC', 'ABCDE')),
            array('ABCDEFGHIJ', array('ABCDEFGHIJ')),
            // This is not a range in POP3 IDs
            array('10:12', array('10:12'))
        );
    }

    public function testPop3Sort()
    {
        $ids = new Horde_Imap_Client_Ids_Pop3(array(
            'ABC',
            'A',
            'AC',
            'AB'
        ));

        $this->assertEquals(
            'ABC A AC AB',
            $ids->tostring_sort
        );
    }

}
