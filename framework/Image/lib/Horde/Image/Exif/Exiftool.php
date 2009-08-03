<?php
/**
 * Exiftool driver for reading/writing image meta data
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Exif_Exiftool extends Horde_Image_Exif_Base
{
    /**
     * Path to exiftool binary
     *
     * @var string
     */
    protected $_exiftool;

    public function __construct($params)
    {
        parent::__construct($params);
        if (!empty($this->_params['exiftool'])) {
            $this->_exiftool = $this->_params['exiftool'];
        } else {
            throw new InvalidArgumentException('Missing required exiftool path');
        }
    }

    /**
     *
     * @return unknown_type
     */
    public function getData($image)
    {
        // Request the full stream of meta data in JSON format.
        $command = '-j ' . $image;
        $test = $this->_execute($command);
        $results = json_decode($this->_execute($command));
        var_dump($results);
        if ($results instanceof stdClass) {
            return $results;
        }

        throw new Horde_Image_Exception('Unknown error running exiftool command.');
    }

    /**
     * Executes a exiftool command.
     *
     * @param string $command  The command to run
     *
     * @return mixed  The result of the command.
     */
    protected function _execute($command)
    {
        exec($this->_exiftool . ' ' . escapeshellcmd($command), $output, $retval);
        if ($retval) {
            $this->_logErr(sprintf("Error running command: %s"), $command . "\n" . implode("\n", $output));
        }
        if (is_array($output)) {
            $output = implode('', $output);
        }

        return $output;
    }

}