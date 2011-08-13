<?php
/**
 * Class representing vNotes.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Karsten Fourmont <fourmont@gmx.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Icalendar
 */
class Horde_Icalendar_Vnote extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vNote';

    /**
     * Constructor.
     */
    public function __construct($version = '1.1')
    {
        parent::__construct($version);
    }

    /**
     * Sets the version of this component.
     *
     * @see $version
     * @see $oldFormat
     *
     * @param string  A float-like version string.
     */
    public function setVersion($version)
    {
        $this->_oldFormat = $version < 1;
        $this->_version = $version;
    }

    /**
     * Unlike vevent and vtodo, a vnote is normally not enclosed in an
     * iCalendar container. (BEGIN..END)
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        $requiredAttributes['BODY'] = '';
        $requiredAttributes['VERSION'] = '1.1';

        foreach ($requiredAttributes as $name => $default_value) {
            try {
                $this->getAttribute($name);
            } catch (Horde_Icalendar_Exception $e) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VNOTE');
    }

}
