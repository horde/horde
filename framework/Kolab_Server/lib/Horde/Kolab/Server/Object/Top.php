<?php
/**
 * The base class representing Kolab objects stored in the server
 * database.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with Kolab objects stored in
 * the Kolab db.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
abstract class Horde_Kolab_Server_Object_Top
extends Horde_Kolab_Server_Object_Base
implements Horde_Kolab_Server_Object_Searches
{
    /** Define the possible Kolab object classes */
    const OBJECTCLASS_TOP = 'top';

    /**
     * The attributes defined for this class.
     *
     * @var array
     */
    static public $attributes = array(
        'objectClass', 'Openldapaci', 'Guid', 'Id',
        'Createtimestamp', 'Modifytimestamp', 
        'Createtimestampdate', 'Modifytimestampdate',
    );

    static public $object_classes = array(
        self::OBJECTCLASS_TOP,
    );

    /**
     * Sort by this attributes.
     *
     * @var string
     */
    public $sort_by = 'Guid';

    /**
     * Return the filter string to retrieve this object type.
     *
     * @static
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        return new Horde_Kolab_Server_Query_Element_Equals(
            Horde_Kolab_Server_Object_Attribute_Objectclass::NAME,
            self::OBJECTCLASS_TOP
        );
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array &$info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info)
    {
    }

    /**
     * Returns the set of actions supported by this object type.
     *
     * @return array An array of supported actions.
     */
    public function getActions()
    {
        return array();
    }
}
