<?php
/**
 * Test the Quota library.
 *
 * PHP version 5
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Quota library.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_QuotaTest extends PHPUnit_Framework_TestCase
{
    public function testMaildir()
    {
        $quota = IMP_Quota::factory('Maildir', array(
            'path' => dirname(__FILE__) . '/../fixtures'
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
