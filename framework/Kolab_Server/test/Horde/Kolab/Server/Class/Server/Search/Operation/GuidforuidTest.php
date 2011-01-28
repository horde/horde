<?php
/**
 * Test the search operations by uid.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../../../../TestCase.php';

/**
 * Test the search operations by uid.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Search_Operation_GuidforuidTest
extends Horde_Kolab_Server_TestCase
{
    public function setUp()
    {
        $this->structure = $this->getMock('Horde_Kolab_Server_Structure_Interface');
    }

    public function testMethodRestrictkolabHasResultRestrictedToKolabUsers()
    {
        $result = $this->getMock('Horde_Kolab_Server_Result_Interface');
        $result->expects($this->once())
            ->method('asArray')
            ->will($this->returnValue(array('a' => 'a')));
        $this->structure->expects($this->once())
            ->method('find')
            ->with(
                $this->logicalAnd(
                    $this->isRestrictedToKolabUsers(),
                    $this->isSearchingByUid()
                ),
                array('attributes' => 'guid')
            )
            ->will($this->returnValue($result));
        $search = new Horde_Kolab_Server_Search_Operation_Guidforuid($this->structure);
        $criteria = $this->getMock('Horde_Kolab_Server_Query_Element_Interface');
        $this->assertEquals(array('a'), $search->searchGuidForUid('test'));
    }
}