<?php
/**
 * Horde_ActiveSync_Message_Folder class represents a single ActiveSync Folder
 * object.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Folder extends Horde_ActiveSync_Message_Base
{
    public $serverid;
    public $parentid;
    public $displayname;
    public $type;

    public function __construct($params = array())
    {
        $this->_mapping = array (
            Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID => array (self::KEY_ATTRIBUTE => 'serverid'),
            Horde_ActiveSync::FOLDERHIERARCHY_PARENTID => array (self::KEY_ATTRIBUTE => 'parentid'),
            Horde_ActiveSync::FOLDERHIERARCHY_DISPLAYNAME => array (self::KEY_ATTRIBUTE => 'displayname'),
            Horde_ActiveSync::FOLDERHIERARCHY_TYPE => array (self::KEY_ATTRIBUTE => 'type')
        );

        $this->_properties = array(
            'serverid' => false,
            'parentid' => false,
            'displayname' => false,
            'type' => false,
        );

        parent::__construct($params);
    }

    public function getClass()
    {
        return 'Folders';
    }
}