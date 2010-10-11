<?php
/**
 * Exiftool driver for reading/writing image meta data
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
        if (empty($this->_params['exiftool'])) {
            throw new InvalidArgumentException('Missing required exiftool path');
        }
        parent::__construct($params);
        $this->_exiftool = $this->_params['exiftool'];
    }

    /**
     *
     * @return unknown_type
     */
    public function getData($image)
    {
        // Request the full stream of meta data in JSON format.
        // -j option outputs in JSON, appending '#' to the -TAG prevents
        // screen formatting.
        $categories = Horde_Image_Exif::getCategories();
        $tags = '';
        foreach (array('EXIF', 'IPTC', 'XMP') as $category) {
            foreach ($categories[$category] as $field => $value) {
                $tags .= ' -' . $field . '#';
            }
        }
        foreach ($categories['COMPOSITE'] as $field => $value) {
            $tags .= ' -' . $field;
        }
        $command = '-j' . $tags . ' ' . $image;
        $results = json_decode($this->_execute($command));
        if (is_array($results)) {
            return $this->_processData((array)array_pop($results));
        }

        throw new Horde_Image_Exception('Unknown error running exiftool command');
    }

    public function supportedCategories()
    {
        return array('EXIF', 'IPTC', 'XMP', 'COMPOSITE');
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
            $this->_logErr(sprintf("Error running command: %s", $command . "\n" . implode("\n", $output)));
        }
        if (is_array($output)) {
            $output = implode('', $output);
        }

        return $output;
    }

}