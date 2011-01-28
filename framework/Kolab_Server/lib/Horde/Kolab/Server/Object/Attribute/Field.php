<?php
/**
 * The base class representing Kolab object attributes.
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
 * The base class representing Kolab object attributes.
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
abstract class Horde_Kolab_Server_Object_Attribute_Field
extends Horde_Kolab_Server_Object_Attribute_Base
{


    /**
     * Quote field separators within a LDAP value.
     *
     * @param string $string The string that should be quoted.
     *
     * @return string The quoted string.
     */
    protected function quote($string)
    {
        return str_replace(array('\\',   '$',),
                           array('\\5c', '\\24',),
                           $string);
    }

    /**
     * Unquote a LDAP value.
     *
     * @param string $string The string that should be unquoted.
     *
     * @return string The unquoted string.
     */
    protected function unquote($string)
    {
        return str_replace(array('\\5c', '\\24',),
                           array('\\',   '$',),
                           $string);
    }


    /**
     * Get a derived attribute value by returning a given position in a
     * delimited string.
     *
     * @param string $basekey   Name of the attribute that holds the
     *                          delimited string.
     * @param string $field     The position of the field to retrieve.
     * @param string $separator The field separator.
     * @param int    $max_count The maximal number of fields.
     *
     * @return mixed The value of the attribute.
     */
    protected function getField($basekey, $field = 0, $separator = '$', $max_count = null)
    {
        $base = $this->_get($basekey);
        if (empty($base)) {
            return;
        }
        if (!empty($max_count)) {
            $fields = explode($separator, $base, $max_count);
        } else {
            $fields = explode($separator, $base);
        }
        return isset($fields[$field]) ? $this->unquote($fields[$field]) : null;
    }


    /**
     * Set a collapsed attribute value.
     *
     * @param string  $key        The attribute to collapse into.
     * @param array   $attributes The attributes to collapse.
     * @param array   $info       The information currently working on.
     * @param string  $separator  Separate the fields using this character.
     * @param boolean $unset      Unset the base values.
     *
     * @return NULL.
     */
    protected function setField($key, $attributes, &$info, $separator = '$', $unset = true)
    {
        /**
         * Check how many empty entries we have at the end of the array. We
         * may omit these together with their field separators.
         */
        krsort($attributes);
        $empty = true;
        $end   = count($attributes);
        foreach ($attributes as $attribute) {
            /**
             * We do not expect the callee to always provide all attributes
             * required for a collapsed attribute. So it is necessary to check
             * for old values here.
             */
            if (!isset($info[$attribute])) {
                $old = $this->get($attribute);
                if (!empty($old)) {
                    $info[$attribute] = $old;
                }
            }
            if ($empty && empty($info[$attribute])) {
                $end--;
            } else {
                $empty = false;
            }
        }
        if ($empty) {
            return;
        }
        ksort($attributes);
        $unset = $attributes;
        $result = '';
        for ($i = 0; $i < $end; $i++) {
            $akey = array_shift($attributes);
            $value =  $info[$akey];
            if (is_array($value)) {
                $value = $value[0];
            }
            $result .= $this->quote($value);
            if ($i != ($end - 1)) {
                $result .= $separator;
            }
        }
        if ($unset === true) {
            foreach ($unset as $attribute) {
                unset($info[$attribute]);
            }
        }
        $info[$key] = $result;
    }
}