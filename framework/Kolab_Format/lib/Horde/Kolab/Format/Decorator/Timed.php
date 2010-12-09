<?php
/**
 * Determines how much time is spent while loading/saving the Kolab objects.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Determines how much time is spent while loading/saving the Kolab objects.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Decorator_Timed
extends Horde_Kolab_Format_Decorator_Base
{
    /**
     * The timer used for recording the amount of time spent.
     *
     * @var Horde_Support_Timer
     */
    private $_timer;

    /**
     * Time spent handling objects.
     *
     * @var float
     */
    static private $_spent = 0.0;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Format  $handler The handler to be decorated.
     * @param Horde_Support_Timer $timer   The timer.
     */
    public function __construct(
        Horde_Kolab_Format $handler,
        Horde_Support_Timer $timer
    ) {
        parent::__construct($handler);
        $this->_timer = $timer;
    }

    /**
     * Load an object based on the given XML string.
     *
     * @param string &$xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load(&$xmltext)
    {
        $this->_timer->push();
        $result = $this->getHandler()->load($xmltext);
        self::$_spent += $this->_timer->pop();
        return $result;
    }

    /**
     * Convert the data to a XML string.
     *
     * @param array &$object The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object)
    {
        $this->_timer->push();
        $result = $this->getHandler()->save($object);
        self::$_spent += $this->_timer->pop();
        return $result;
    }

    /**
     * Report the time spent for loading/saving objects.
     *
     * @return float The amount of time.
     */
    public function timeSpent()
    {
        return self::$_spent;
    }
}