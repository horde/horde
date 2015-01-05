<?php
/**
 * Horde_ActiveSync_Message_DocumentLibrary:: Defines an object representing
 * a DOCUMENTLIBRARY search result.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2014-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_DocumentLibrary
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2014-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 */
class Horde_ActiveSync_Message_DocumentLibrary extends Horde_ActiveSync_Message_Base
{

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LINKID           => array(self::KEY_ATTRIBUTE => 'linkid'),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_DISPLAYNAME      => array(self::KEY_ATTRIBUTE => 'displayname'),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_ISFOLDER         => array(self::KEY_ATTRIBUTE => 'isfolder'),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_CREATIONDATE     => array(self::KEY_ATTRIBUTE => 'creationdate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LASTMODIFIEDDATE => array(self::KEY_ATTRIBUTE => 'lastmodifieddate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_ISHIDDEN         => array(self::KEY_ATTRIBUTE => 'ishidden'),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_CONTENTLENGTH    => array(self::KEY_ATTRIBUTE => 'contentlength'),
        Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_CONTENTTYPE      => array(self::KEY_ATTRIBUTE => 'contenttype')
    );

    /**
     * Property values
     *
     * @var array
     */
    protected $_properties = array(
        'linkid'           => false,
        'displayname'      => false,
        'isfolder'         => false,
        'creationdate'     => false,
        'lastmodifieddate' => false,
        'ishidden'         => false,
        'contentlength'    => false,
        'contenttype'      => 'application/octet-stream'
    );

}
