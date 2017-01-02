<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseDelete::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_AirSyncBaseAdd::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseDelete extends Horde_ActiveSync_Message_Base
{

    /**
     * Property mappings
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_FILEREFERENCE => array(self::KEY_ATTRIBUTE => 'filereference')
    );

    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_properties = array(
        'filereference' => false
    );

    /**
     * Return the type of message.
     *
     * @return string
     */
    public function getClass()
    {
        return 'AirSyncBaseDelete';
    }

}
