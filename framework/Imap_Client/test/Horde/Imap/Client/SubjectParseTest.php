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
        $imap_utils = new Horde_Imap_Client_Utils();

        $subjects = array(
            'Re: Test',
            're: Test',
            'Fwd: Test',
            'fwd: Test',
            'Fwd: Re: Test',
            'Fwd: Re: Test (fwd)',
        );

        foreach ($subjects as $val) {
            $this->assertEquals(
                'Test',
                $imap_utils->getBaseSubject($val)
            );
        }
    }

    public function testSubjectParseTabs()
    {
        $imap_utils = new Horde_Imap_Client_Utils();

        $this->assertEquals(
            "Test",
            $imap_utils->getBaseSubject("Re: re:re: fwd:[fwd: \t  Test]  (fwd)  (fwd)(fwd) ")
        );
    }

}
