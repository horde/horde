<?php
/**
 * Test the push factory.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the push factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Push
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push_Unit_Push_Factory_PushTest
extends Horde_Push_TestCase
{
    public function tearDown()
    {
        $GLOBALS['push'] = null;
    }

    public function testEmpty()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array(),
            array(),
            array()
        );
        $this->assertEquals('', $push[0]->getSummary());
    }

    public function testYaml()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('yaml://' . __DIR__ . '/../../../fixtures/push.yaml'),
            array(),
            array()
        );
        $this->assertEquals('YAML', $push[0]->getSummary());
    }

    public function testPhp()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('php://' . __DIR__ . '/../../../fixtures/push.php'),
            array(),
            array()
        );
        $this->assertEquals('PHP', $push[0]->getSummary());
    }

    public function testKolab()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('kolab://INBOX/test/libkcal-543769073.132'),
            array(),
            array(
                'kolab' => array(
                    'driver' => 'mock',
                    'queryset' => array('list' => array('queryset' => 'horde')),
                    'params' => array(
                        'username' => 'test',
                        'host' => 'localhost',
                        'port' => 143,
                        'data' => array(
                            'format' => 'brief',
                            'user/test'  => array(),
                            'user/test/test'  => array(
                                't' => 'note.default',
                                'm' => array(
                                    1 => array(
                                        'structure' => __DIR__ . '/../../../fixtures/note.struct',
                                        'parts' => array(
                                            '2' => array(
                                                'file' => __DIR__ . '/../../../fixtures/note.xml.qp',
                                            )
                                        )
                                    )
                                ),
                                's' => array(
                                    'uidvalidity' => '12346789',
                                    'uidnext' => 2
                                )
                            )
                        )
                    )
                )
            )
        );
        $this->assertEquals('Summary', $push[0]->getSummary());
    }

    public function testMultiple()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array(
                'php://' . __DIR__ . '/../../../fixtures/push.php',
                'yaml://' . __DIR__ . '/../../../fixtures/push.yaml'
            ),
            array(),
            array()
        );
        $this->assertEquals('PHP', $push[0]->getSummary());
        $this->assertEquals('YAML', $push[1]->getSummary());
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testMissingPhp()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('php://' . __DIR__ . '/../../../fixtures/DOES_NOT_EXIST'),
            array(),
            array()
        );
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testEmptySummary()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('php://' . __DIR__ . '/../../../fixtures/empty.php'),
            array(),
            array()
        );
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testMissingYaml()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('yaml://' . __DIR__ . '/../../../fixtures/DOES_NOT_EXIST'),
            array(),
            array()
        );
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testEmptyArgument()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('yaml://'),
            array(),
            array()
        );
    }

    /**
     * @expectedException Horde_Push_Exception
     */
    public function testUnknownArgument()
    {
        $factory = new Horde_Push_Factory_Push();
        $push = $factory->create(
            array('NOSUCH://XYZ'),
            array(),
            array()
        );
    }
}
