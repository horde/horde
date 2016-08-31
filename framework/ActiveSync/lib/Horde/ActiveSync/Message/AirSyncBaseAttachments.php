<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseAttachments::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_AirSyncBaseAttachment::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseAttachments extends Horde_ActiveSync_Message_Base
{
    /* Attachement types */
    const ATT_TYPE_NORMAL   = 1;
    const ATT_TYPE_EMBEDDED = 5;

    /**
     * Property mappings
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_ATTACHMENT => array(
            self::KEY_ATTRIBUTE => 'attachment',
            self::KEY_VALUES => Horde_ActiveSync::AIRSYNCBASE_ATTACHMENT,
            self::KEY_PROPERTY => self::PROPERTY_NO_CONTAINER
        )
    );

    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_properties = array(
        'attachment' => false,
    );

    /**
     * Const'r
     *
     * @see Horde_ActiveSync_Message_Base::__construct()
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if ($this->_version >= Horde_ActiveSync::VERSION_SIXTEEN) {
            $this->_mapping += array(
                Horde_ActiveSync::AIRSYNCBASE_ADD => array(
                    self::KEY_ATTRIBUTE => 'add',
                    self::KEY_TYPE => 'Horde_ActiveSync_Message_AirSyncBaseAdd',
                    self::KEY_PROPERTY => self::PROPERTY_MULTI_ARRAY
                ),
                Horde_ActiveSync::AIRSYNCBASE_DELETE => array(
                    self::KEY_ATTRIBUTE => 'delete',
                    self::KEY_TYPE => 'Horde_ActiveSync_Message_AirSyncBaseDelete',
                    self::KEY_PROPERTY => self::PROPERTY_MULTI_ARRAY,
                )
            );

            $this->_properties += array(
                'add'                  => false,
                'delete'               => false,
            );
        }
    }

    /**
     * Return the type of message.
     *
     * @return string
     */
    public function getClass()
    {
        return 'AirSyncBaseAttachments';
    }

}
