<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseFileAttachment::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_AirSyncFileAttachment::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseFileAttachment extends Horde_ActiveSync_Message_Base
{
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_CONTENTTYPE => array(self::KEY_ATTRIBUTE => 'contenttype'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_DATA => array(self::KEY_ATTRIBUTE => 'data'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_TOTAL => array(self::KEY_ATTRIBUTE => 'total'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_RANGE => array(self::KEY_ATTRIBUTE => 'range')
    );

    protected $_properties = array(
        'contenttype' => false,
        'data' => false,
        'total' => false,
        'range' => false
    );

    public function getClass()
    {
        return 'AirSyncBaseFileAttachment';
    }

    /**
     * Checks if the data needs to be encoded like e.g., when outputing binary
     * data in-line during ITEMOPERATIONS requests. Concrete classes should
     * override this if needed.
     *
     * @param mixed  $data  The data to check. A string or stream resource.
     * @param string $tag   The tag we are outputing.
     *
     * @return mixed  The encoded data. A string or stream resource with
     *                a filter attached.
     */
    protected function _checkEncoding($data, $tag)
    {
        if ($tag == Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_DATA) {
            if (is_resource($data)) {
                stream_filter_append($data, 'convert.base64-encode');
            } else {
                $data = base64_encode($data);
            }
        }

        return $data;
    }

}
