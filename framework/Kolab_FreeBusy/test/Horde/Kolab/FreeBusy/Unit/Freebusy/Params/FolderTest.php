<?php
/**
 * Test retrieving the folder parameter.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test retrieving the folder parameter.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_Freebusy_Params_FolderTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testGetFolder()
    {
        
        $param = new Horde_Kolab_FreeBusy_Freebusy_Params_Folder(
            $this->getTestMatchDict('trigger')
        );
        $this->assertEquals('Kalender', $param->getResource());
    }

    public function testGetOwner()
    {
        $param = new Horde_Kolab_FreeBusy_Freebusy_Params_Folder(
            $this->getTestMatchDict('trigger')
        );
        $this->assertEquals('owner@example.org', $param->getOwner());
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testInvalidFolder()
    {
        $param = new Horde_Kolab_FreeBusy_Freebusy_Params_Folder(
            $this->getTestMatchDict('invalid')
        );
    }

    public function testFetchOwner()
    {
        $param = new Horde_Kolab_FreeBusy_Freebusy_Params_Folder(
            $this->getTestMatchDict('fetch')
        );
        $this->assertEquals('owner@example.org', $param->getOwner());
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testFetchResource()
    {
        $param = new Horde_Kolab_FreeBusy_Freebusy_Params_Folder(
            $this->getTestMatchDict('fetch')
        );
        $param->getResource();
    }

    /**
     * @expectedException Horde_Kolab_FreeBusy_Exception
     */
    public function testEmpty()
    {
        $param = new Horde_Kolab_FreeBusy_Freebusy_Params_Folder(
            $this->getTestMatchDict('empty')
        );
        $param->getOwner();
    }

}