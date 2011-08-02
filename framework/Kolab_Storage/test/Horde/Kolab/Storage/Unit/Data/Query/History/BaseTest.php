<?php
/**
 * Test the handling of the history data query.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the handling of the history data query.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Data_Query_History_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testSynchronize()
    {
        $prefs = $this->_getDataQuery()->synchronize();
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
                            1 => array('file' => dirname(__FILE__) . '/../../../../fixtures/preferences.1'),
                            2 => array('file' => dirname(__FILE__) . '/../../../../fixtures/preferences.1'),
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