<?php
/**
 * Factory for Ansel_Faces
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Factory_Faces extends Horde_Core_Factory_Injector
{
    public function create (Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['faces']['driver'];
        $params = $GLOBALS['conf']['faces'];
        $class_name = 'Ansel_Faces_' . ucfirst($driver);

        return new $class_name($params);
    }

}
