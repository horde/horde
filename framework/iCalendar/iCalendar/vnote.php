<?php
/**
 * Class representing vNotes.
 *
 * $Horde: framework/iCalendar/iCalendar/vnote.php,v 1.14 2009/06/07 18:13:46 mrubinsk Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Karsten Fourmont <fourmont@gmx.de>
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vnote extends Horde_iCalendar {

    /**
     * The component type of this class.
     *
     * @var string
     */
    var $type = 'vNote';

    function Horde_iCalendar_vnote($version = '1.1')
    {
        return parent::Horde_iCalendar($version);
    }

    /**
     * Sets the version of this component.
     *
     * @see $version
     * @see $oldFormat
     *
     * @param string  A float-like version string.
     */
    function setVersion($version)
    {
        $this->oldFormat = $version < 1;
        $this->version = $version;
    }

    /**
     * Unlike vevent and vtodo, a vnote is normally not enclosed in an
     * iCalendar container. (BEGIN..END)
     */
    function exportvCalendar()
    {
        $requiredAttributes['BODY'] = '';
        $requiredAttributes['VERSION'] = '1.1';

        foreach ($requiredAttributes as $name => $default_value) {
            if (is_a($this->getattribute($name), 'PEAR_Error')) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VNOTE');
    }

}
