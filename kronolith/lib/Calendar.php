<?php
/**
 * Kronolith_Calendar defines an API for single calendars.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_Calendar
{
    /**
     * Constructor.
     *
     * @param array $params  A hash with any parameters that this calendar
     *                       might need.
     */
    public function __construct($params = array())
    {
        foreach ($params as $param => $value) {
            $this->{'_' . $param} = $value;
        }
    }

    /**
     * Returns the owner of this calendar.
     *
     * @return string  This calendar's owner.
     */
    public function owner()
    {
        return $GLOBALS['registry']->getAuth();
    }

    /**
     * Returns the name of this calendar.
     *
     * @return string  This calendar's name.
     */
    abstract public function name();

    /**
     * Returns the description of this calendar.
     *
     * @return string  This calendar's description.
     */
    public function description()
    {
        return '';
    }

    /**
     * Returns the background color for this calendar.
     *
     * @return string  A HTML color code.
     */
    public function background()
    {
        return '#dddddd';
    }

    /**
     * Returns the foreground color for this calendar.
     *
     * @return string  A HTML color code.
     */
    public function foreground()
    {
        return Horde_Image::brightness($this->background()) < 128 ? '#fff' : '#000';
    }

    /**
     * Returns the CSS color definition for this calendar.
     *
     * @param boolean $with_attribute  Whether to wrap the colors inside a
     *                                 "style" attribute.
     *
     * @return string  A CSS string with color definitions.
     */
    public function css($with_attribute = true)
    {
        $css = 'background-color:' . $this->background() . ';color:' . $this->foreground();
        if ($with_attribute) {
            $css = ' style="' . $css . '"';
        }
        return $css;
    }

    /**
     * Encapsulates permissions checking.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for. Defaults
     *                             to the current user.
     * @param string $creator      An event creator, to check for creator
     *                             permissions.
     *
     * @return boolean  Whether the user has the permission on this calendar.
     */
    public function hasPermission($permission, $user = null, $creator = null)
    {
        switch ($permission) {
        case Horde_Perms::SHOW:
        case Horde_Perms::READ:
            return true;

        default:
            return false;
        }
    }

    /**
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    abstract public function display();

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        return array(
            'name'  => $this->name(),
            'desc'  => $this->description(),
            'owner' => true,
            'fg'    => $this->foreground(),
            'bg'    => $this->background(),
        );
    }
}
