<?php
/**
 * Horde_ActiveSync_Message_RecipientInformation::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_RecipientInformation::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property string   $email1address
 * @property string   $fileas
 * @property string   $alias (EAS >= 14.0 only)
 * @property string   $weightedrank (EAS >= 14.0 only)
 */
class Horde_ActiveSync_Message_RecipientInformation extends Horde_ActiveSync_Message_Base
{
    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync_Message_Contact::EMAIL1ADDRESS  => array(self::KEY_ATTRIBUTE => 'email1address'),
        Horde_ActiveSync_Message_Contact::FILEAS         => array(self::KEY_ATTRIBUTE => 'fileas'),
        Horde_ActiveSync_Message_Contact::ALIAS          => array(self::KEY_ATTRIBUTE => 'alias'),
        Horde_ActiveSync_Message_Contact::WEIGHTEDRANK   => array(self::KEY_ATTRIBUTE => 'weightedrank'),
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'email1address' => false,
        'fileas'        => false,
        'alias'         => false,
        'weightedrank'   => false,
    );

    /**
     * Return message type
     *
     * @return string
     */
    public function getClass()
    {
        return 'RI';
    }

}
