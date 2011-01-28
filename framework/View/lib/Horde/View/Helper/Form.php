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
class Horde_View_Helper_Form extends Horde_View_Helper_Base
{
    private $_instanceTag = 'Horde_View_Helper_Form_InstanceTag_Form';

    public function formFor($objectName)
    {
        $args = func_get_args();
        $options = (is_array(end($args))) ? array_pop($args) : array();

        if (isset($options['url'])) {
            $urlOptions = $options['url'];
            unset($options['url']);
        } else {
            $urlOptions = array();
        }

        if (isset($options['html'])) {
            $htmlOptions = $options['html'];
            unset($options['url']);
        } else {
            $htmlOptions = array();
        }
        echo $this->formTag($urlOptions, $htmlOptions);

        $options['end'] = '</form>';

        array_push($args, $options);
        return call_user_func_array(array($this, 'fieldsFor'), $args);
    }

    public function fieldsFor($objectName)
    {
        $args = func_get_args();
        $options = (is_array(end($args))) ? array_pop($args) : array();
        $object  = isset($args[1]) ? $args[1] : null;

        $builder = isset($options['builder']) ? $options['builder']
                                              : Horde_View_Base::$defaultFormBuilder;

        return new $builder($objectName, $object, $this->_view, $options);
    }

    /**
     * Returns a label tag tailored for labelling an input field for a specified
     * attribute (identified by +method+) on an object assigned to the template
     * (identified by +object+). The text of label will default to the attribute
     * name unless you specify it explicitly. Additional options on the label
     * tag can be passed as a hash with +options+. These options will be tagged
     * onto the HTML as an HTML element attribute as in the example shown.
     *
     * ==== Examples
     *   label(:post, :title)
     *   # => <label for="post_title">Title</label>
     *
     *   label(:post, :title, "A short title")
     *   # => <label for="post_title">A short title</label>
     *
     *   label(:post, :title, "A short title", :class => "title_label")
     *   # => <label for="post_title" class="title_label">A short title</label>
     */
    public function label($objectName, $method, $text, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toLabelTag($text, $options);
    }

    public function textField($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toInputFieldTag('text', $options);
    }

    public function passwordField($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toInputFieldTag('password', $options);
    }

    public function hiddenField($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toInputFieldTag('hidden', $options);
    }

    public function fileField($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toInputFieldTag('file', $options);
    }

    public function checkBox($objectName, $method, $options = array(),
                             $checkedValue = '1', $uncheckedValue = '0')
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toCheckBoxTag($options, $checkedValue, $uncheckedValue);
    }

    public function radioButton($objectName, $method, $tagValue, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toRadioButtonTag($tagValue, $options);
    }

    public function textArea($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toTextAreaTag($options);
    }

}
