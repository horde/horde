<?php
/**
 * Factory for Ansel_Faces
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Injector_Factory_Faces
{
    public function create (Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['faces']['driver'];
        $params = $GLOBALS['conf']['faces'];
        $class_name = 'Ansel_Faces_' . ucfirst($driver);

        return new $class_name($params);
    }

}