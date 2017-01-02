<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseLocation::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2016-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_AirSyncBaseLocation::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2016-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseLocation extends Horde_ActiveSync_Message_Base
{

    /**
     * Property map
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_ACCURACY => array(self::KEY_ATTRIBUTE => 'accuracy'),
        Horde_ActiveSync::AIRSYNCBASE_ALTITUDE => array(self::KEY_ATTRIBUTE => 'altitude'),
        Horde_ActiveSync::AIRSYNCBASE_ALTITUDEACCURACY => array(self::KEY_ATTRIBUTE => 'altitudeaccuracy'),
        Horde_ActiveSync::AIRSYNCBASE_ANNOTATION => array(self::KEY_ATTRIBUTE => 'annotation'),
        Horde_ActiveSync::AIRSYNCBASE_CITY => array(self::KEY_ATTRIBUTE => 'city'),
        Horde_ActiveSync::AIRSYNCBASE_COUNTRY => array(self::KEY_ATTRIBUTE => 'country'),
        Horde_ActiveSync::AIRSYNCBASE_DISPLAYNAME => array(self::KEY_ATTRIBUTE => 'displayname'),
        Horde_ActiveSync::AIRSYNCBASE_LATITUDE => array(self::KEY_ATTRIBUTE => 'latitude'),
        Horde_ActiveSync::AIRSYNCBASE_LOCATIONURI => array(self::KEY_ATTRIBUTE => 'locationuri'),
        Horde_ActiveSync::AIRSYNCBASE_LONGITUDE => array(self::KEY_ATTRIBUTE => 'longitude'),
        Horde_ActiveSync::AIRSYNCBASE_POSTALCODE => array(self::KEY_ATTRIBUTE => 'postalcode'),
        Horde_ActiveSync::AIRSYNCBASE_STATE => array(self::KEY_ATTRIBUTE => 'state'),
        Horde_ActiveSync::AIRSYNCBASE_STREET => array(self::KEY_ATTRIBUTE => 'street'),
    );

    /**
     * Property values
     *
     * @var array
     */
    protected $_properties = array(
        'accuracy' => false,
        'altitude' => false,
        'altitudeaccuracy' => false,
        'annotation' => false,
        'city' => false,
        'country' => false,
        'displayname' => false,
        'latitude' => false,
        'locationuri' => false,
        'longitude' => false,
        'postalcode' => false,
        'state' => false,
        'street' => false,
    );

    /**
     * Return the message type.
     *
     * @return string
     */
    public function getClass()
    {
        return 'AirSyncBaseFileAttachment';
    }

    /**
     * Checks if the data needs to be encoded like e.g., when outputing binary
     * data in-line during ITEMOPERATIONS requests.
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
            // See Bug: 14086. Use a STREAM_FILTER_WRITE and perform the
            // filtering here instead of using the currently broken behavior of
            // PHP when using base64-encode as STREAM_FILTER_READ. feof() is
            // apparently not safe to use when using STREAM_FILTER_READ.
            if (is_resource($data)) {
                 $temp = fopen('php://temp/', 'r+');
                 $filter = stream_filter_prepend($temp, 'convert.base64-encode', STREAM_FILTER_WRITE);
                 rewind($data);
                 while (!feof($data)) {
                     fwrite($temp, fread($data, 8192));
                 }
                 stream_filter_remove($filter);
                 rewind($temp);
                return $temp;
            } else {
                return base64_encode($data);
            }
        }

        return $data;
    }

}
