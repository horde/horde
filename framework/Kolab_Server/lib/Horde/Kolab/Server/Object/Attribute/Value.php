<?php
/**
 * The base class representing Kolab object attributes.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The base class representing Kolab object attributes.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Attribute_Value
extends Horde_Kolab_Server_Object_Attribute_Base
{
    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the value failed.
     */
    public function value()
    {
        $internal = $this->attribute->value();
        if (isset($internal[$this->name])) {
            return $internal[$this->name];
        } else {
            throw new Horde_Kolab_Server_Exception(sprintf('Missing value %s!', $this->name));
        }
    }

    /**
     * Indicate that a value will be saved by deleting it from the original data
     * array.
     *
     * @param array &$changes The object data that should be changed.
     *
     * @return NULL
     */
    public function consume(array &$changes)
    {
        if (isset($changes[$this->name])) {
            unset($changes[$this->name]);
        }
    }

    /**
     * Return the new internal state for this attribute.
     *
     * @param array $changes The object data that should be updated.
     *
     * @return array The resulting internal state.
     *
     * @throws Horde_Kolab_Server_Exception If storing the value failed.
     */
    public function update(array $changes)
    {
        if (!$this->isEmpty($changes)) {
            $value = $changes[$this->name];
            if (!is_array($value)) {
                $value = array($value);
            }
            return $this->attribute->update($value);
        }
        try {
            $old = $this->attribute->value();
            return $this->attribute->update(array());
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            return array();
        }
    }

}