<?php
/**
 * Representation of a Kolab server object.
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
 * This class provides methods to deal with the Kolab server configuration
 * object.
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
class Horde_Kolab_Server_Object_Kolab extends Horde_Kolab_Server_Object_Groupofnames
{
    /** Define attributes specific to this object type */

    /** The name attribute for this type of object class */
    const ATTRIBUTE_K = 'k';

    /**
     * How many days into the past should the free/busy data on the server be
     * calculated?
     */
    const ATTRIBUTE_FBPAST = 'kolabFreeBusyPast';

    /** The specific object class of this object type */
    const OBJECTCLASS_KOLAB = 'kolab';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_K,
            self::ATTRIBUTE_FBPAST,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLAB,
        ),
    );

    /**
     * Return the filter string to retrieve this object type.
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        $criteria = array('AND' => array(
                              array('field' => self::ATTRIBUTE_K,
                                    'op'    => '=',
                                    'test'  => 'kolab'),
                              array('field' => self::ATTRIBUTE_OC,
                                    'op'    => '=',
                                    'test'  => self::OBJECTCLASS_KOLAB),
                          ),
        );
        return $criteria;
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array &$info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(array &$info)
    {
        return self::ATTRIBUTE_K . '=kolab';
    }
}
