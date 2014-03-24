<?php
/**
 * Horde_ActiveSync_Message_ResolveRecipientsPicture::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_ResolveRecipientsPicture:: Encapsulate the picture
 * data to send in a RESOLVERECIPIENTS response.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property integer status       The status of the recipient's picture.
 * @property string|stream  data  The picture data.
 *
 */
class Horde_ActiveSync_Message_ResolveRecipientsPicture extends Horde_ActiveSync_Message_Base
{
    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync_Message_ResolveRecipients::TAG_STATUS => array(self::KEY_ATTRIBUTE => 'status'),
        Horde_ActiveSync_Message_ResolveRecipients::TAG_DATA   => array(self::KEY_ATTRIBUTE => 'data')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'status' => false,
        'data'   => false
    );

}