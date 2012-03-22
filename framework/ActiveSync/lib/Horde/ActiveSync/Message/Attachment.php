<?php
/**
 * Horde_ActiveSync_Message_Attachement class represents a single attachemnt.
 *
 * @copyright 2010-2011 Horde LLC (http://www.horde.org)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
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
