<?php
/**
 * Tests for Subject parsing.
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
 * Tests for Subject parsing.
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
class Horde_Imap_Client_SubjectParseTest extends PHPUnit_Framework_TestCase
{
    public function testSubjectParse()
    {
        $subjects = array(
            'Test',
            'Re: Test',
            're: Test',
            'Fwd: Test',
            'fwd: Test',
            'Fwd: Re: Test',
            'Fwd: Re: Test (fwd)',
            '  re    :   Test  (fwd)',
            '  re :   [foo]Test(Fwd)',
            "re \t: \tTest"
        );

        foreach ($subjects as $val) {
            $this->assertEquals(
                'Test',
                strval(new Horde_Imap_Client_Data_BaseSubject($val))
            );
        }

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
