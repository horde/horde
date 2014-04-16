<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for Subject parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_SubjectParseTest extends PHPUnit_Framework_TestCase
{
    public function provider()
    {
        // Format: Test string, Expected parse result
        return array(
            array('Test', 'Test'),
            array('Re: Test', 'Test'),
            array('re: Test', 'Test'),
            array('Fwd: Test', 'Test'),
            array('fwd: Test', 'Test'),
            array('Fwd: Re: Test', 'Test'),
            array('Fwd: Re: Test (fwd)', 'Test'),
            array('  re    :   Test  (fwd)', 'Test'),
            array('  re :   [foo]Test(Fwd)', 'Test'),
            array("re \t: \tTest", 'Test'),
            array('Re:', ''),
            array(' RE :  ', ''),
            array('Fwd:', ''),
            array('  FWD  :   ', '')
        );
    }

    /**
     * @dataProvider provider
     */
    public function testSubjectParse($subject, $expected)
    {
        $this->assertEquals(
            $expected,
            strval(new Horde_Imap_Client_Data_BaseSubject($subject))
        );
    }

    public function testUndefinedIndexError()
    {
        // This used to throw an undefined index error.
        $this->assertEquals(
            'fwd',
            strval(new Horde_Imap_Client_Data_BaseSubject('fwd'))
        );
    }

    public function testSubjectParseTabs()
    {
        $this->assertEquals(
            "Test",
            strval(new Horde_Imap_Client_Data_BaseSubject("Re: re:re: fwd:[fwd: \t  Test]  (fwd)  (fwd)(fwd) "))
        );
    }

}
