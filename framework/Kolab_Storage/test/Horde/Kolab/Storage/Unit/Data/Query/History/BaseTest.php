<?php
/**
 * Test the handling of the history data query.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the handling of the history data query.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Data_Query_History_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testSynchronize()
    {
        $this->_getDataQuery();
        $this->assertEquals(
            1,
            count(
                $this->history->getHistory(
                    '20080626155721.771268tms63o0rs4@devmail.example.com'
                )
            )
        );
    }

    public function testAdded()
    {
        $this->_getDataQuery();
        $this->assertEquals(
            array(
                '20080626155721.771268tms63o0rs4@devmail.example.com' => 1,
                '20090731103253.11391snjudt9zgpw@webmail.example.com' => 2
            ),
            $this->history->getByTimestamp(
                '>',
                time() - 10,
                array(
                    array(
                        'field' => 'action',
                        'op' => '=',
                        'value' => 'add'
                    )
                )
            )
        );
    }

    public function testSingleAdd()
    {
        $this->_getDataQuery()->synchronize();
        $this->assertEquals(
            array(
                '20080626155721.771268tms63o0rs4@devmail.example.com' => 1,
                '20090731103253.11391snjudt9zgpw@webmail.example.com' => 2
            ),
            $this->history->getByTimestamp(
                '>',
                time() - 10,
                array(
                    array(
                        'field' => 'action',
                        'op' => '=',
                        'value' => 'add'
                    )
                )
            )
        );
    }

    public function testModify()
    {
        $data = $this->_getData();
        $o = $data->getObject('20090731103253.11391snjudt9zgpw@webmail.example.com');
        $data->modify($o);
        $this->assertEquals(
            array(
                '20090731103253.11391snjudt9zgpw@webmail.example.com' => 3
            ),
            $this->history->getByTimestamp(
                '>',
                time() - 10,
                array(
                    array(
                        'field' => 'action',
                        'op' => '=',
                        'value' => 'modify'
                    )
                )
            )
        );
    }

    public function testDelete()
    {
        $data = $this->_getData();
        $data->delete('20090731103253.11391snjudt9zgpw@webmail.example.com');
        $this->assertEquals(
            array(
                '20090731103253.11391snjudt9zgpw@webmail.example.com' => 3
            ),
            $this->history->getByTimestamp(
                '>',
                time() - 10,
                array(
                    array(
                        'field' => 'action',
                        'op' => '=',
                        'value' => 'delete'
                    )
                )
            )
        );
    }

    private function _getData()
    {
        return $this->_getFolder()->getData('INBOX/History');
    }

    private function _getDataQuery()
    {
        return $this->_getData()
            ->getQuery(Horde_Kolab_Storage_Data::QUERY_HISTORY);
    }

    private function _getFolder()
    {
        $this->history = new Horde_History_Mock('test');
        return $this->getDataStorage(
            $this->getDataAccount(
                array(
                    'user/test/History' => array(
                        't' => 'h-prefs.default',
                        'm' => array(
                            1 => array('file' => __DIR__ . '/../../../../fixtures/preferences.1'),
                            2 => array('file' => __DIR__ . '/../../../../fixtures/preferences.2'),
                        ),
                    )
                )
            ),
            array(
                'queryset' => array('data' => array('queryset' => 'horde')),
                'history' => $this->history
            )
        );
    }
}