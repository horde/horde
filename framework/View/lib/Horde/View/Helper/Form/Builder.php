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
class Horde_View_Helper_Form_Builder
{
    private $_objectName;
    private $_object;
    private $_view;
    private $_options;
    private $_end;

    public function __construct($objectName, $object, $view, $options)
    {
        $this->_objectName = $objectName;
        $this->_object     = $object;
        $this->_view       = $view;

        $this->_end = isset($options['end']) ? $options['end'] : '';
        unset($options['end']);
        $this->_options = $options;
    }

    public function __call($method, $args)
    {
        if (empty($args)) {
            throw new InvalidArgumentException('No object property specified');
        }
        $objectProperty = $args[0];
        $options        = array_merge(isset($args[1]) ? $args[1] : array(),
                                      array('object' => $this->_object));

        return $this->_view->{$method}($this->_objectName, $objectProperty, $options);
    }

    public function fieldsFor($name) {
        $name = "{$this->_objectName}[$name]";
        $args = func_get_args();
        $args[0] = $name;
        return call_user_func_array(array($this->_view, 'fieldsFor'), $args);
    }

    public function checkBox($method, $options = array(), $checkedValue = '1', $uncheckedValue = '0')
    {
        $options = array_merge($options, array('object' => $this->_object));
        return $this->_view->checkBox($this->_objectName, $method, $options, $checkedValue, $uncheckedValue);
    }

    public function radioButton($method, $tagValue, $options = array())
    {
        $options = array_merge($options, array('object' => $this->_object));
        return $this->_view->radioButton($this->_objectName, $method, $tagValue, $options);
    }

    // @todo error_message_on
    // @todo error_messages

    public function submit($value = 'Save changes', $options = array())
    {
        $options = array_merge(array('id' => "{$this->_objectName}_submit"), $options);
        return $this->_view->submitTag($value, $options);
    }

    public function end()
    {
        echo $this->_end;
    }

}
