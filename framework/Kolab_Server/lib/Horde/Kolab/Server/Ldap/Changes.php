<?php
/**
 * A helper class to determine an LDAP changeset.
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
 * A helper class to determine an LDAP changeset.
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
class Horde_Kolab_Server_Ldap_Changes
{
    /**
     * The object to be modified.
     *
     * @var Horde_Kolab_Server_Object
     */
    private $_object;

    /**
     * The new dataset.
     *
     * @var array
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Object $object The object to be modified.
     * @param array                     $data   The attributes of the object
     *                                          to be stored.
     */
    public function __construct(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        $this->_object = $object;
        $this->_data   = $data;
    }

    /**
     * Return an LDAP changeset from the difference between the current object
     * data and the new dataset.
     *
     * @return array The LDAP changeset.
     */
    public function getChangeset()
    {
        $cs         = array();
        $old        = $this->_object->readInternal();
        $new        = $this->_data;
        $attributes = array_merge(array_keys($old), array_keys($new));
        foreach ($attributes as $attribute) {
            if (!isset($old[$attribute])) {
                $cs['add'][$attribute] = $new[$attribute];
                continue;
            }
            if (!isset($new[$attribute])) {
                $cs['delete'][] = $attribute;
                continue;
            }
            if (count($new[$attribute]) == 1
                && count($old[$attribute]) == 1
            ) {
                if ($new[$attribute][0] == $old[$attribute][0]) {
                    continue;
                } else {
                    $cs['replace'][$attribute] = $new[$attribute][0];
                    continue;
                }
            }
            $adds = array_diff($new[$attribute], $old[$attribute]);
            if (!empty($adds)) {
                $cs['add'][$attribute] = array_values($adds);
            }
            $deletes = array_diff($old[$attribute], $new[$attribute]);
            if (!empty($deletes)) {
                $cs['delete'][$attribute] = array_values($deletes);
            }
        }
        return $cs;
    }
}