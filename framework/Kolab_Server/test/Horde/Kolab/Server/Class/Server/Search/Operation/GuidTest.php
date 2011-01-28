<?php
/**
 * Test the guid search operation.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the guid search operation.
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
class Horde_Kolab_Server_Class_Server_Search_Operation_GuidTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->structure = $this->getMock('Horde_Kolab_Server_Structure_Interface');
    }

    public function testMethodConstructHasParameterStructure()
    {
        $search = new Horde_Kolab_Server_Search_Operation_Guid($this->structure);
    }

    public function testMethodConstructHasPostconditionThatTheServerStructureGetsStored()
    {
        $search = new Horde_Kolab_Server_Search_Operation_Guid($this->structure);
        $this->assertSame($this->structure, $search->getStructure());
    }

    public function testMethodGetStructureHasResultStructureTheStructureAssociatedWithThisSearch()
    {
        $search = new Horde_Kolab_Server_Search_Operation_Guid($this->structure);
        $this->assertType('Horde_Kolab_Server_Structure_Interface', $search->getStructure());
    }

    public function testMethodSearchguidHasResultArrayTheGuidsOfTheSearchResult()
    {
        $result = $this->getMock('Horde_Kolab_Server_Result_Interface');
        $result->expects($this->once())
            ->method('asArray')
            ->will($this->returnValue(array('a' => 'a')));
        $this->structure->expects($this->once())
            ->method('find')
            ->with(
                $this->isInstanceOf(
                    'Horde_Kolab_Server_Query_Element_Interface'
                ),
                array('attributes' => 'guid')
            )
            ->will($this->returnValue($result));
        $search = new Horde_Kolab_Server_Search_Operation_Guid($this->structure);
        $criteria = $this->getMock('Horde_Kolab_Server_Query_Element_Interface');
        $this->assertEquals(array('a'), $search->searchGuid($criteria));
    }

    public function testMethodSearchguidHasResultArrayEmptyIfTheSearchReturnedNoResults()
    {
        $result = $this->getMock('Horde_Kolab_Server_Result_Interface');
        $result->expects($this->once())
            ->method('asArray')
            ->will($this->returnValue(array()));
        $this->structure->expects($this->once())
            ->method('find')
            ->with(
                $this->isInstanceOf(
                    'Horde_Kolab_Server_Query_Element_Interface'
                ),
                array('attributes' => 'guid')
            )
            ->will($this->returnValue($result));
        $search = new Horde_Kolab_Server_Search_Operation_Guid($this->structure);
        $criteria = $this->getMock('Horde_Kolab_Server_Query_Element_Interface');
        $this->assertEquals(array(), $search->searchGuid($criteria));
    }
}
