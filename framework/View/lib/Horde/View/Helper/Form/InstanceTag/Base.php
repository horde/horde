<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Form_InstanceTag_Base extends Horde_View_Helper_Tag
{
    protected $_defaultFieldOptions = array('size' => 30);
    protected $_defaultRadioOptions = array();
    protected $_defaultTextAreaOptions = array('cols' => 40, 'rows' => 20);
    protected $_defaultDateOptions = array('discardType' => true);

    protected $objectName;
    protected $objectProperty;
    protected $object;
    protected $autoIndex;

    /**
     * @param  array  $values  Values to cycle through
     */
    public function __construct($objectName, $objectProperty, $view, $object = null)
    {
        $this->_view = $view;
        $this->objectProperty = $objectProperty;
        $this->object = $object;

        if (strpos($objectName, '[]')) {
            $objectName = rtrim($objectName, '[]');
            if (! isset($object)) {
                $object = $view->{$objectName};
            }
            if (isset($object) && isset($object->id_before_type_cast)) {
                $this->autoIndex = $object->id_before_type_cast;
            } else {
                $msg = "object[] naming but object param and @object var don't exist or don't respond to id_before_type_cast";
                throw new InvalidArgumentException($msg);
            }
        }

        $this->objectName = $objectName;
    }

    public function object()
    {
        if (isset($this->object)) {
            return $this->object;
        } else {
            return $this->_view->{$this->objectName};
        }
    }

    public function value($object)
    {
        if (is_object($object)) {
            return $object->{$this->objectProperty};
        } else {
            return null;
        }
    }

    protected function valueBeforeTypeCast($object)
    {
        if (is_object($object)) {
            if (isset($object->{"{$this->objectProperty}_before_type_cast"})) {
                return $object->{"{$this->objectProperty}_before_type_cast"};
            } else {
                if (isset($object->{$this->objectProperty})) {
                    return $object->{$this->objectProperty};
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }
    }

    protected function addDefaultNameAndId($options)
    {
        if (isset($options['index'])) {
            if (! isset($options['name'])) {
                $options['name'] = $this->tagNameWithIndex($options['index']);
            }
            if (! isset($options['id'])) {
                $options['id'] = $this->tagIdWithIndex($options['index']);
            }
            unset($options['index']);
        } else if (isset($this->autoIndex)) {
            if (! isset($options['name'])) {
                $options['name'] = $this->tagNameWithIndex($this->autoIndex);
            }
            if (! isset($options['id'])) {
                $options['id'] = $this->tagIdWithIndex($this->autoIndex);
            }
        } else {
            if (! isset($options['name'])) {
                $options['name'] = $this->tagName()
                                 . (isset($options['multiple']) ? '[]' : '');
            }
            if (! isset($options['id'])) {
                $options['id'] = $this->tagId();
            }
        }
        return $options;
    }

    protected function tagName()
    {
        return "{$this->objectName}[$this->objectProperty]";
    }

    protected function tagNameWithIndex($index)
    {
        return "{$this->objectName}[$index][$this->objectProperty]";
    }

    protected function tagId()
    {
        return $this->sanitizedObjectName() . "_{$this->objectProperty}";
    }

    protected function tagIdWithIndex($index)
    {
        return $this->sanitizedObjectName() . "_{$index}_{$this->objectProperty}";
    }

    protected function sanitizedObjectName()
    {
        $name = preg_replace('/[^-a-zA-Z0-9:.]/', '_', $this->objectName);
        $name = preg_replace('/_$/', '', $name);
        return $name;
    }

}
