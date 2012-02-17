<?php
/**
 * Exiftool driver for reading/writing image meta data
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
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
        if (empty($this->_params['exiftool'])) {
            throw new InvalidArgumentException('Missing required exiftool path');
        }
        $this->_exiftool = $this->_params['exiftool'];
    }

    /**
     * Get the image's EXIF data.
     *
     * @param string $image  The path to an image.
     *
     * @return array  The exif data.
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
        $output = array();
        $retval = null;
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