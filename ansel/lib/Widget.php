<?php
/**
 * Ansel_Widget:: class wraps the display of widgets to be displayed in various
 * Ansel_Views.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license http://www.fsf.org/copyleft/gpl.html GPL
 * @package Ansel
 */
class Ansel_Widget
{
    /**
     * Factory method for creating Ansel_Widgets
     *
     * @param string $driver  The type of widget to create.
     * @param array $params   Any parameters the widget needs.
     *
     * @return mixed Ansel_Widget object | PEAR_Error
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = 'Ansel_Widget_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }
}
