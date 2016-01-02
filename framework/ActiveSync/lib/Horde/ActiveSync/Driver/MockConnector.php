<?php
/**
 * Horde_ActiveSync_Driver_MockConnector::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Mock connector for testing using the Horde_ActiveSync_Driver_Mock driver.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Driver_MockConnector
{

    /**
     * By default, support the main groupware apps, minus mail.
     * Mock this method to override.
     */
    public function listApis()
    {
        return array('calendar', 'contacts', 'tasks', 'notes');
    }

    /**
     * By default, return 2 UIDs as shown below. Mock this method to override.
     */
    public function listUids()
    {
        return array('UID_001', 'UID_002');
    }

    /**
     * By default, return no changes. Mock this method to override.
     */
    public function getChanges($folderid, $from_ts, $to_ts)
    {
        return array(
            'add' => array(),
            'modify' => array(),
            'delete' => array());
    }

    /**
     * Always returns a MODSEQ of 2. Mock to override.
     */
    public function getActionTimestamp($id, $action)
    {
        return 2;
    }

    /**
     * MUST mock this method if needed so we can return the expected object.
     *
     * @return Horde_ActiveSync_Message_Base
     */
    public function export($id, $options)
    {

    }




}