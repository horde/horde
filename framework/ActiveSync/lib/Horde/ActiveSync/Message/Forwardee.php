<?php
/**
 * Horde_ActiveSync_Message_Forwardee::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Picture:: Encapsulate the data to send in a
 * GAL response.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 */
class Horde_ActiveSync_Message_Forwardee extends Horde_ActiveSync_Message_Base
{
    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync_Message_SendMail::FORWARDEENAME  => array(self::KEY_ATTRIBUTE => 'name'),
        Horde_ActiveSync_Message_SendMail::FORWARDEEEMAIL => array(self::KEY_ATTRIBUTE => 'email')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'name' => false,
        'email'   => false
    );

}