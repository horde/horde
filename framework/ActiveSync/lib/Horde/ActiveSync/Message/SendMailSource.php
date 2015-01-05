<?php
/**
 * Horde_ActiveSync_Message_SendMailSource::
 *
 * Portions of this class were ported from the Z-Push project:
 * File      :   syncsendmail.php
 * Project   :   Z-Push
 * Descr     :   WBXML sendmail entities that
 *               can be parsed directly (as a
 *               stream) from WBXML.
 *               It is automatically decoded
 *               according to $mapping,
 *               and the Sync WBXML mappings.
 *
 * Created   :   30.01.2012
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
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_SendMailSource::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property string   $folderid    The item's folderid.
 * @property string   $itemid      The item's itemid.
 * @property string   $longid      The item's longid.
 * @property string   $instanceid  The item's instanceid.
 */
class Horde_ActiveSync_Message_SendMailSource extends Horde_ActiveSync_Message_Base
{
    const COMPOSEMAIL_FOLDERID        = 'ComposeMail:FolderId';
    const COMPOSEMAIL_ITEMID          = 'ComposeMail:ItemId';
    const COMPOSEMAIL_LONGID          = 'ComposeMail:LongId';
    const COMPOSEMAIL_INSTANCEID      = 'ComposeMail:InstanceId';

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array (
        self::COMPOSEMAIL_FOLDERID   => array(self::KEY_ATTRIBUTE => 'folderid'),
        self::COMPOSEMAIL_ITEMID     => array(self::KEY_ATTRIBUTE => 'itemid'),
        self::COMPOSEMAIL_LONGID     => array(self::KEY_ATTRIBUTE => 'longid'),
        self::COMPOSEMAIL_INSTANCEID => array(self::KEY_ATTRIBUTE => 'instanceid')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'folderid'   => false,
        'itemid'     => false,
        'longid'     => false,
        'instanceid' => false,
    );

    /**
     * Return this object's folder class
     *
     * @return string
     */
    public function getClass()
    {
        return 'SendMailSource';
    }

}