<?php
/**
 * This class provides the data structure for a message flag.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property string $abbreviation  The abbreviation to use in the mimp view.
 * @property string $bgcolor  The background color.
 * @property boolean $bgdefault  Is the backgroud color the default?
 * @property boolean $canset  Can this flag be set by the user?
 * @property string $css  The CSS class for the icon when the flag is set.
 * @property string $cssicon  The CSS class for the icon.
 * @property string $fgcolor  The foreground (text) color.
 * @property string $form_set  Form value to use when setting flag.
 * @property string $form_unset  Form value to use when unsetting flag.
 * @property string $id  Unique ID.
 * @property string $label  The query label.
 * @property string $span  Return SPAN HTML to output the icon for use in a
 *                         mailbox row.
 */
abstract class IMP_Flag_Base implements Serializable
{
    /* Default background color. */
    const DEFAULT_BG = '#fff';

    /**
     * The abbreviation.
     *
     * @var string
     */
    protected $_abbreviation = '';

    /**
     * The background color.
     *
     * @var string
     */
    protected $_bgcolor = '';

    /**
     * Is this flag settable by the user?
     *
     * @var boolean
     */
    protected $_canset = false;

    /**
     * The CSS class.
     *
     * @var string
     */
    protected $_css = '';

    /**
     * The CSS class for the icon.
     *
     * @var string
     */
    protected $_cssIcon = '';

    /**
     * Unique ID.
     *
     * @var string
     */
    protected $_id = '';

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'abbreviation':
            return $this->_abbreviation;

        case 'bgcolor':
            return $this->_bgcolor
                ? $this->_bgcolor
                : self::DEFAULT_BG;

        case 'bgdefault':
            return ($this->bgcolor == self::DEFAULT_BG);

        case 'canset':
            return $this->_canset;

        case 'css':
            return $this->_css;

        case 'cssicon':
            return $this->_cssIcon
                ? $this->_cssIcon
                : $this->_css;

        case 'span':
            return $this->_css
                ? '<span class="iconImg msgflags ' . $this->css . '" title="' . htmlspecialchars($this->label) . '">&nbsp;</span>'
                : '';

        case 'fgcolor':
            return (Horde_Image::brightness($this->bgcolor) < 128)
                ? '#f6f6f6'
                : '#000';

        case 'form_set':
            return $this->id;

        case 'form_unset':
            return '0\\' . $this->id;

        case 'id':
            return $this->_id;

        case 'label':
            return $this->getLabel();
        }
    }

    /**
     * Set properties.
     *
     * @param string $name   Available properties:
     *   - bgcolor: (string) The background color.
     * @param string $value  Property value.
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'bgcolor':
            $this->_bgcolor = ($value == self::DEFAULT_BG)
                ? ''
                : $value;
            break;
        }
    }

    /**
     * Given a list of flag objects, determines if this flag's status has
     * changed.
     *
     * @param array $obs    A list of IMP_Flag_Base objects.
     * @param boolean $add  True if these flags were added, false if they were
     *                      removed.
     *
     * @return mixed  Null if no change, true if flag is added, false if flag
     *                is removed.
     */
    public function changed($obs, $add)
    {
        return null;
    }

    /**
     * Return the flag label.
     *
     * @param boolean $set  Return label for setting the flag?
     *
     * @return string  The label.
     */
    public function getLabel($set = true)
    {
        return $set
            ? $this->_getLabel()
            : sprintf(_("Not %s"), $this->_getLabel());
    }

    /**
     * Determines if the flag exists given some input data.
     *
     * @param mixed $data  The input data to check.
     *
     * @return boolean  True if flag exists.
     */
    public function match($data)
    {
        return false;
    }

    /**
     * Return the flag label.
     * Necessary evil as gettext strings can not be set directly to object
     * properties.
     *
     * @return string  The label.
     */
    abstract protected function _getLabel();

    /* Magic methods. */

    /**
     * String representation of the object.
     *
     * @return string  String representation (Flag ID).
     */
    public function __toString()
    {
        return $this->id;
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $this->_bgcolor;
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_bgcolor = $data;
    }

}
