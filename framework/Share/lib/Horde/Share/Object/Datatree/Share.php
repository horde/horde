<?php
/**
 * Extension of the DataTreeObject class for storing Share information in the
 * DataTree driver. If you want to store specialized Share information, you
 * should extend this class instead of extending DataTreeObject directly.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Share
 */
class Horde_Share_Object_DataTree_Share extends DataTreeObject
{
    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['datatree']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Maps this object's attributes from the data array into a format that we
     * can store in the attributes storage backend.
     *
     * @access protected
     *
     * @param boolean $permsonly  Only process permissions? Lets subclasses
     *                            override part of this method while handling
     *                            their additional attributes seperately.
     *
     * @return array  The attributes array.
     */
    protected function _toAttributes($permsonly = false)
    {
        // Default to no attributes.
        $attributes = array();

        foreach ($this->data as $key => $value) {
            if ($key == 'perm') {
                foreach ($value as $type => $perms) {
                    if (is_array($perms)) {
                        foreach ($perms as $member => $perm) {
                            $attributes[] = array('name' => 'perm_' . $type,
                                                  'key' => $member,
                                                  'value' => $perm);
                        }
                    } else {
                        $attributes[] = array('name' => 'perm_' . $type,
                                              'key' => '',
                                              'value' => $perms);
                    }
                }
            } elseif (!$permsonly) {
                $attributes[] = array('name' => $key,
                                      'key' => '',
                                      'value' => $value);
            }
        }

        return $attributes;
    }

    /**
     * Takes in a list of attributes from the backend and maps it to our
     * internal data array.
     *
     * @access protected
     *
     * @param array $attributes   The list of attributes from the backend
     *                            (attribute name, key, and value).
     * @param boolean $permsonly  Only process permissions? Lets subclasses
     *                            override part of this method while handling
     *                            their additional attributes seperately.
     */
    protected function _fromAttributes($attributes, $permsonly = false)
    {
        // Initialize data array.
        $this->data['perm'] = array();

        foreach ($attributes as $attr) {
            if (substr($attr['name'], 0, 4) == 'perm') {
                if (!empty($attr['key'])) {
                    $this->data['perm'][substr($attr['name'], 5)][$attr['key']] = $attr['value'];
                } else {
                    $this->data['perm'][substr($attr['name'], 5)] = $attr['value'];
                }
            } elseif (!$permsonly) {
                $this->data[$attr['name']] = $attr['value'];
            }
        }
    }
}
