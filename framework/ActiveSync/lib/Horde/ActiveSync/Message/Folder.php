<?php
/**
 * Horde_ActiveSync_Message_Folder class represents a single ActiveSync Folder
 * object.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Message_Folder extends Horde_ActiveSync_Message_Base
{
    public $serverid;
    public $parentid;
    public $displayname;
    public $type;

    protected $_mapping = array (
        Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID => array (self::KEY_ATTRIBUTE => 'serverid'),
        Horde_ActiveSync::FOLDERHIERARCHY_PARENTID      => array (self::KEY_ATTRIBUTE => 'parentid'),
        Horde_ActiveSync::FOLDERHIERARCHY_DISPLAYNAME   => array (self::KEY_ATTRIBUTE => 'displayname'),
        Horde_ActiveSync::FOLDERHIERARCHY_TYPE          => array (self::KEY_ATTRIBUTE => 'type')
    );

    protected $_properties = array(
        'serverid'    => false,
        'parentid'    => false,
        'displayname' => false,
        'type'        => false,
    );

    public function getClass()
    {
        return 'Folders';
    }

}