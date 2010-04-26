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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test retrieving the folder parameter.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Class_Params_Freebusy_FolderTest
extends PHPUnit_Framework_TestCase
{
    public function testMethodGetfolderRetrievesFolderNameFromPost()
    {
        $_POST['folder'] = 'wrobel/test';
        $param = new Horde_Kolab_FreeBusy_Params_Freebusy_Folder(
            new Horde_Controller_Request_Http(
                array(
                    'session_control' => 'none'
                )
            )
        );
        $this->assertEquals('test', $param->getFolder());
    }
}