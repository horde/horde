<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * POP3 specific tests for the Ids object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Ids_Pop3Test extends PHPUnit_Framework_TestCase
{
    public function testPop3SequenceStringGenerate()
    {
        $this->assertEquals(
            'ABCDEFGHIJ ABCDE',
            strval(new Horde_Imap_Client_Ids_Pop3(array('ABCDEFGHIJ', 'ABCDE')))
        );

        $this->assertEquals(
            'ABCDEFGHIJ',
            strval(new Horde_Imap_Client_Ids_Pop3('ABCDEFGHIJ'))
        );
    }

    public function testPop3SequenceStringParse()
    {
        $ids = new Horde_Imap_Client_Ids_Pop3('ABCDEFGHIJ ABCDE');
        $this->assertEquals(
            array('ABCDEFGHIJ', 'ABCDE'),
            $ids->ids
        );

        $ids = new Horde_Imap_Client_Ids_Pop3('ABCDEFGHIJ ABC ABCDE');
        $this->assertEquals(
            array('ABCDEFGHIJ', 'ABC', 'ABCDE'),
            $ids->ids
        );

        $ids = new Horde_Imap_Client_Ids_Pop3('10:12');
        $this->assertEquals(
            array('10:12'),
            $ids->ids
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
