<?php
/**
 * Horde_ActiveSync_Utils_Mime::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Utils_Mime:: contains general utilities.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 */
class Horde_ActiveSync_Utils_Mime
{
    /**
     * Return the body type to send to the client, based on the various
     * OPTIONS requested by the client.
     *
     * @param array $collection   The collection array.
     *
     * @return integer  A Horde_ActiveSync::BODYPREF_TYPE_* constant.
     */
    public static function getBodyTypePref($collection, $save_bandwidth = true)
    {
        $bodyprefs = $collection['bodyprefs'];
        if ($save_bandwidth) {
            return !empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML])
                ? Horde_ActiveSync::BODYPREF_TYPE_HTML
                : (!empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME])
                    ? Horde_ActiveSync::BODYPREF_TYPE_MIME
                    : Horde_ActiveSync::BODYPREF_TYPE_PLAIN);
        }

        // Prefer high bandwidth, full MIME.
        return !empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME])
            ? Horde_ActiveSync::BODYPREF_TYPE_MIME
            : (!empty($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML])
                ? Horde_ActiveSync::BODYPREF_TYPE_HTML
                : Horde_ActiveSync::BODYPREF_TYPE_PLAIN);
    }

}