<?php
/**
 * Test the Quota library.
 *
 * PHP version 5
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the Quota library.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
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
