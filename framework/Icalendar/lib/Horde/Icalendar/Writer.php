<?php

class Horde_Icalendar_Writer
{

    /**
     * Attempts to return a concrete Horde_Icalendar_Writer instance based on
     * $format and $version.
     *
     * @param mixed $format   The format that the writer should output.
     * @param array $version  The format version.
     *
     * @return Horde_Icalendar_Writer  The newly created concrete instance.
     *
     * @throws Horde_Icalendar_Exception
     */
    static public function factory($format, $version)
    {
        $class = 'Horde_Icalendar_Writer_' . $format . '_' . $version;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Icalendar_Exception($class . ' not found.');
    }

}
