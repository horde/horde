<?php
/**
 * Test the handling of the history data query.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Test the handling of the history data query.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
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
                    'mnemo:internal_id:ABC1234'
                )
            )
        );
    }

    public function testAdded()
    {
        $this->_getDataQuery();
        $this->assertEquals(
            array(
                'mnemo:internal_id:ABC1234' => 1,
                'mnemo:internal_id:DEF5678' => 2
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
        // TODO: What is the purpose of this test exactly?
        // It looks pretty identical to the test above.
        $this->_getDataQuery()->synchronize();
        $this->assertEquals(
            array(
                'mnemo:internal_id:ABC1234' => 1,
                'mnemo:internal_id:DEF5678' => 2
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
        $o = $data->getObject('DEF5678');
        $data->modify($o);

        $this->assertEquals(
            array(
                'mnemo:internal_id:DEF5678' => 3
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
        $data->delete('ABC1234');

        $this->assertEquals(
            array(
                'mnemo:internal_id:ABC1234' => 3
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

    public function testStaleHistoryCleanup()
    {
        $data = $this->_getData();
        $data_query = $data->getQuery(Horde_Kolab_Storage_Data::QUERY_HISTORY);

        $this->history->log(
            'mnemo:internal_id:stale_entry_uid', array('action' => 'add', 'bid' => '123'), true
        );

        // Sync history and check for cleanup
        $data_query->synchronize();

        $this->assertEquals(
            2,
            count($this->history->getHistory('mnemo:internal_id:stale_entry_uid'))
        );
        $this->assertEquals(
            array(
                'mnemo:internal_id:stale_entry_uid' => 4
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

        // Check that other entries are still there
        $this->assertEquals(
            array(
                'mnemo:internal_id:ABC1234' => 1,
                'mnemo:internal_id:DEF5678' => 2,
                'mnemo:internal_id:stale_entry_uid' => 3
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
                        'a' => array(
                            '/shared/vendor/kolab/folder-type' => 'note.default',
                            '/shared/vendor/horde/share-params' => base64_encode(serialize(array('share_name' => 'internal_id')))
                        ),
                        'm' => array(
                            1 => array('file' => __DIR__ . '/../../../../fixtures/note.eml'),
                            2 => array('file' => __DIR__ . '/../../../../fixtures/note2.eml'),
                        ),
                    )
                )
            ),
            array(
                'queryset' => array('data' => array('queryset' => 'horde')),
                'queries' => array(
                    'list' => array(
                        Horde_Kolab_Storage_List_Tools::QUERY_BASE => array(
                            'cache' => false
                        ),
                        Horde_Kolab_Storage_List_Tools::QUERY_ACL => array(
                            'cache' => false
                        ),
                        Horde_Kolab_Storage_List_Tools::QUERY_SHARE => array(
                            'cache' => false
                        ),
                    )
                ),
                'history' => $this->history
            )
        );
    }
}
