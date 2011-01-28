<?php
/**
 * This class provides the data structure for a message flag.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
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
     * Get object properties.
     *
     * @param string $name  Available properties:
     * <pre>
     * 'abbreviation' - (string) The abbreviation to use in the mimp view.
     * 'bgcolor' - (string) The background color.
     * 'bgdefault' - (boolean) Is the backgroud color the default?
     * 'canset' - (boolean) Can this flag be set by the user?
     * 'css' - (string) The CSS class for the icon when the flag is set.
     * 'cssicon' - (string) The CSS class for the icon.
     * 'div' - (string) Return DIV HTML to output the icon for use in a
     *         mailbox row.
     * 'fgcolor' - (string) The foreground (text) color.
     * 'form_set' - (string) Form value to use when setting flag.
     * 'form_unset' - (string) Form value to use when unsetting flag.
     * 'id' - (string) Unique ID.
     * 'label' - (string) The query label.
     * </pre>
     *
     * @return mixed  Property value.
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

        case 'div':
            return $this->_css
                ? '<div class="iconImg msgflags ' . $this->css . '" title="' . htmlspecialchars($this->label) . '"></div>'
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
     * <pre>
     * 'bgcolor' - (string) The background color.
     * </pre>
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
    abstract public function match($data);

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
