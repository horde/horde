<?php
/**
 * Horde_ActiveSync_Message_Attachment
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
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Attachment
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_Attachment extends Horde_ActiveSync_Message_Base
{
    /* Wbxml constants */
    const POOMMAIL_ATTNAME           = 'POOMMAIL:AttName';
    const POOMMAIL_ATTSIZE           = 'POOMMAIL:AttSize';
    const POOMMAIL_ATTOID            = 'POOMMAIL:AttOid';
    const POOMMAIL_ATTMETHOD         = 'POOMMAIL:AttMethod';
    const POOMMAIL_ATTREMOVED        = 'POOMMAIL:AttRemoved';
    const POOMMAIL_DISPLAYNAME       = 'POOMMAIL:DisplayName';

    /* Attachement types */
    const ATT_TYPE_NORMAL   = 1;
    const ATT_TYPE_EMBEDDED = 5;
    const ATT_TYPE_OLE      = 6;

    /**
     * Property mappings
     *
     * @var array
     */
    protected $_mapping = array(
        self::POOMMAIL_ATTMETHOD   => array (self::KEY_ATTRIBUTE => "attmethod"),
        self::POOMMAIL_ATTSIZE     => array (self::KEY_ATTRIBUTE => "attsize"),
        self::POOMMAIL_DISPLAYNAME => array (self::KEY_ATTRIBUTE => "displayname"),
        self::POOMMAIL_ATTNAME     => array (self::KEY_ATTRIBUTE => "attname"),
        self::POOMMAIL_ATTOID      => array (self::KEY_ATTRIBUTE => "attoid"),
        self::POOMMAIL_ATTREMOVED  => array (self::KEY_ATTRIBUTE => "attremoved"),
    );

    protected $_properties = array(
        'attmethod'   => false,
        'attsize'     => false,
        'displayname' => false,
        'attname'     => false,
        'attoid'      => false,
        'attremoved'  => false
    );

    public function getClass()
    {
        return 'Attachment';
    }

}
