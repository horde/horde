<?php
/**
 * Tests for sequence string parsing.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Tests for sequence string parsing.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_IdsParseTest extends PHPUnit_Framework_TestCase
{
    public function testPop3SequenceStringGenerate()
    {
        $pop3_utils = new Horde_Imap_Client_Utils_Pop3();

        $this->assertEquals(
            '{P10}ABCDEFGHIJ{P5}ABCDE',
            $pop3_utils->toSequenceString(array('ABCDEFGHIJ', 'ABCDE'))
        );

        $this->assertEquals(
            '{P10}ABCDEFGHIJ',
            $pop3_utils->toSequenceString('ABCDEFGHIJ')
        );

        $this->assertEquals(
            '{P10}ABCDEFGHIJ',
            $pop3_utils->toSequenceString(array('ABCDEFGHIJ'))
        );
    }

    public function testPop3SequenceStringParse()
    {
        $pop3_utils = new Horde_Imap_Client_Utils_Pop3();

        $this->assertEquals(
            array('ABCDEFGHIJ', 'ABCDE'),
            $pop3_utils->fromSequenceString('{P10}ABCDEFGHIJ{P5}ABCDE')
        );

        $this->assertEquals(
            array('ABCDEFGHIJ', 'ABCDE'),
            $pop3_utils->fromSequenceString('{P10}ABCDEFGHIJ{P5}ABCDEFGHIJ')
        );

        $this->assertEquals(
            array('ABCDEFGHIJ'),
            $pop3_utils->fromSequenceString('{P10}ABCDEFGHIJ{5}ABCDEFGHIJ')
        );

        $this->assertEquals(
            array('{10}ABCDEFGHIJ'),
            $pop3_utils->fromSequenceString('{10}ABCDEFGHIJ')
        );
    }

}
