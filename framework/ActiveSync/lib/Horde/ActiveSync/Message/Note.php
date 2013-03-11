<?php
/**
 * Horde_ActiveSync_Message_Note::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Note::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property string subject  The note's subject.
 * @property Horde_ActiveSync_Message_AirSyncBaseBody body  The note's body.
 * @property string messageclass  The note's message class.
 * @property array categories  The note's categories.
 * @property Horde_Date lastmodified  The note's last modification date.
 */
class Horde_ActiveSync_Message_Note extends Horde_ActiveSync_Message_Base
{

    const SUBJECT          = 'Notes:Subject';
    const MESSAGECLASS     = 'Notes:MessageClass';
    const LASTMODIFIEDDATE = 'Notes:LastModifiedDate';
    const CATEGORIES       = 'Notes:Categories';
    const CATEGORY         = 'Notes:Category';

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array (
        Horde_ActiveSync::SYNC_AIRSYNCBASE_BODY => array(self::KEY_ATTRIBUTE => 'body', self::KEY_TYPE => 'Horde_ActiveSync_Message_AirSyncBaseBody'),
        self::CATEGORIES                        => array(self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::CATEGORY),
        self::LASTMODIFIEDDATE                  => array(self::KEY_ATTRIBUTE => 'lastmodified', self::KEY_TYPE => self::TYPE_DATE),
        self::MESSAGECLASS                      => array(self::KEY_ATTRIBUTE => 'messageclass'),
        self::SUBJECT                           => array(self::KEY_ATTRIBUTE => 'subject')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'body'         => false,
        'categories'   => false,
        'lastmodified' => false,
        'messageclass' => false,
        'subject'      => false
    );

}