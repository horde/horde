<?php
/**
 * Horde_ActiveSync_Message_SendMail::
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
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_SendMail::
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
class Horde_ActiveSync_Message_SendMail extends Horde_ActiveSync_Message_Base
{
    const COMPOSEMAIL_SENDMAIL        = 'ComposeMail:SendMail';
    const COMPOSEMAIL_SMARTFORWARD    = 'ComposeMail:SmartForward';
    const COMPOSEMAIL_SMARTREPLY      = 'ComposeMail:SmartReply';
    const COMPOSEMAIL_SAVEINSENTITEMS = 'ComposeMail:SaveInSentItems';
    const COMPOSEMAIL_REPLACEMIME     = 'ComposeMail:ReplaceMime';
    const COMPOSEMAIL_TYPE            = 'ComposeMail:Type';
    const COMPOSEMAIL_SOURCE          = 'ComposeMail:Source';
    const COMPOSEMAIL_MIME            = 'ComposeMail:MIME';
    const COMPOSEMAIL_CLIENTID        = 'ComposeMail:ClientId';
    const COMPOSEMAIL_STATUS          = 'ComposeMail:Status';
    const COMPOSEMAIL_ACCOUNTID       = 'ComposeMail:AccountId';

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array (
        self::COMPOSEMAIL_CLIENTID        => array(self::KEY_ATTRIBUTE => 'clientid'),
        self::COMPOSEMAIL_SAVEINSENTITEMS => array(self::KEY_ATTRIBUTE => 'saveinsent'),
        self::COMPOSEMAIL_REPLACEMIME     => array(self::KEY_ATTRIBUTE => 'replacemime'),
        self::COMPOSEMAIL_ACCOUNTID       => array(self::KEY_ATTRIBUTE => 'accountid'),
        self::COMPOSEMAIL_SOURCE          => array(self::KEY_ATTRIBUTE => 'source', self::KEY_TYPE => 'Horde_ActiveSync_Message_SendMailSource'),
        self::COMPOSEMAIL_MIME            => array(self::KEY_ATTRIBUTE => 'mime'),
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'clientid'    => false,
        'saveinsent'  => false,
        'replacemime' => false,
        'accountid'   => false,
        'source'      => false,
        'mime'        => false,
    );

    /**
     * Return this object's folder class
     *
     * @return string
     */
    public function getClass()
    {
        return 'SendMail';
    }

    /**
     * Check if a field should be sent to the device even if it is empty.
     *
     * @param string $tag  The field tag.
     *
     * @return boolean
     */
    protected function _checkSendEmpty($tag)
    {
        if ($tag == self::COMPOSEMAIL_SAVEINSENTITEMS ||
            $tag == self::COMPOSEMAIL_REPLACEMIME) {
            return true;
        }

        return false;
    }

}