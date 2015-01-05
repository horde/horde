<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @copyright  2012-2015 Horde LLC
 * @license    http://www.horde.org/licenses/bsd New BSD License
 * @package    Mail
 * @subpackage UnitTests
 */

/**
 * Test identification fields parsing code.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2012-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/bsd New BSD License
 * @package    Mail
 * @subpackage UnitTests
 */
class Horde_Mail_IdentificationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     */
    public function testParsing($value, $count)
    {
        $ob = new Horde_Mail_Rfc822_Identification($value);

        $this->assertEquals(
            $count,
            count($ob->ids)
        );
    }

    public function provider()
    {
        return array(
            array(
                '<foo@example.com> <foo2@example.com> <foo3@example.com>',
                3
            ),
            array(
                '<foo@example.com><foo2@example.com><foo3@example.com>',
                3
            ),
            array(
                '<foo@example.com>, <foo2@example.com>,<foo3@example.com>',
                3
            ),
            array(
                '<foo@example.com>, <foo2@example.com>,<foo3@example.com> <foo4@example.com>     <foo5@example.com>  ',
                5
            ),
            // Bug #11953
            array(
                '<foo@example@example.com>',
                1
            ),
            // Parse non-compliant IDs
            array(
                'foo@example.com',
                1
            ),
            array(
                'foo@example.com  <foo2@example.com>',
                2
            ),
            array(
                'foo@example.com, <foo2@example.com>',
                2
            )
        );
    }

}
