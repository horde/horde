<?php
/**
 * Test the handling of the preference data query.
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
 * Test the handling of the preference data query.
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
class Horde_Kolab_Storage_Unit_Data_Query_Preferences_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testHordeApplication()
    {
        $prefs = $this->_getDataQuery()->getApplicationPreferences('horde');
        $this->assertEquals(
            '20080626155721.771268tms63o0rs4@devmail.example.com',
            $prefs['uid']
        );
    }

    private function _getData()
    {
        return $this->_getPreferences()->getData('INBOX/Preferences');
    }

    private function _getDataQuery()
    {
        return $this->_getData()
            ->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS);
    }

    private function _getPreferences()
    {
        return $this->getDataStorage(
            $this->getDataAccount(
                array(
                    'user/test/Preferences' => array(
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
            )
        );
    }
}