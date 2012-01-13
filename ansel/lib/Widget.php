<?php
/**
 * Ansel_Widget:: class wraps the display of widgets to be displayed in various
 * Ansel_Views.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license http://www.horde.org/licenses/gpl GPL
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
     * @return Ansel_Widget object
     * @throws Ansel_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = 'Ansel_Widget_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Ansel_Exception('Class definition of ' . $class . ' not found.');
    }

}
