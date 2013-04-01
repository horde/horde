<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @copyright 2010-2013 Horde LLC
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Test the Quota library.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_QuotaTest extends PHPUnit_Framework_TestCase
{
    public function testMaildir()
    {
        $quota = new IMP_Quota_Maildir(array(
            'path' => __DIR__ . '/../fixtures',
            'username' => 'foo'
        ));

        $data = $quota->getQuota();

        $this->assertEquals(
            1000000000,
            $data['limit']
        );

        $this->assertEquals(
            550839239,
            $data['usage']
        );
    }

}
