<?php
/**
 * Horde_ActiveSync_Message_Folder::
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
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Folder::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_Folder extends Horde_ActiveSync_Message_Base
{
    public $parentid = false;

    protected $_mapping = array (
        Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID => array (self::KEY_ATTRIBUTE => 'serverid'),
        Horde_ActiveSync::FOLDERHIERARCHY_PARENTID      => array (self::KEY_ATTRIBUTE => 'parentid'),
        Horde_ActiveSync::FOLDERHIERARCHY_DISPLAYNAME   => array (self::KEY_ATTRIBUTE => 'displayname'),
        Horde_ActiveSync::FOLDERHIERARCHY_TYPE          => array (self::KEY_ATTRIBUTE => 'type')
    );

    protected $_properties = array(
        'serverid'    => false,
        'displayname' => false,
        'type'        => false,
    );

    public function getClass()
    {
        return 'Folders';
    }

}