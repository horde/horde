<?php
/**
 * Hylax_Image Class
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Hylax
 */
class Hylax_Image {

    var $_data;
    var $_cmd;
    var $_pages = array();

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this image driver.
     */
    function Hylax_Image()
    {
        $this->_cmd = array('identify' => '/usr/bin/identify',
                            'convert'  => '/usr/bin/convert',
                            'ps2pdf'   => '/usr/bin/ps2pdf14');
    }

    function loadData($data)
    {
        $this->_data = $data;
    }

    function getDimensions()
    {
        $tmp_file = Horde_Util::getTempFile('fax', true, '/tmp');
        Horde::startBuffer();
        var_dump($tmp_file);
        Horde::logMessage('Created temp file:' . Horde::endBuffer() . ':', 'DEBUG');
        $fp = fopen($tmp_file, 'w');
        fwrite($fp, $this->_data);
        fclose($fp);

        /* Run a ImageMagick identify command on the file to get the details. */
        $command = sprintf('%s %s', $this->_cmd['identify'], $tmp_file);
        Horde::logMessage('External command call by Hylax_Image::getDimensions(): :' . $command . ':', 'DEBUG');
        exec($command, $output, $retval);

        $init = strlen($tmp_file);

        /* Figure out the dimensions from the output. */
        Horde::logMessage('External command output by Hylax_Image::getDimensions(): ' . serialize($output), 'DEBUG');
        foreach ($output as $key => $line) {
            if (substr($line, 0, $init) != $tmp_file) {
                continue;
            }
            $info = explode(' ', $line);
            $dims = explode('+', $info[2]);
            list($width, $height) = explode('x', $dims[0]);
            $this->_pages[$key]['width'] = $width;
            $this->_pages[$key]['height'] = $height;
        }
    }

    function getNumPages()
    {
        if (empty($this->_pages)) {
            $this->getDimensions();
        }

        return count($this->_pages);
    }

    function getImage($page, $preview = false)
    {
        $tmp_file = Horde_Util::getTempFile('fax', true, '/tmp');
        $fp = fopen($tmp_file, 'wb');
        fwrite($fp, $this->_data);
        fclose($fp);

        /* Set resize based on whether preview or not. */
        $resize = ($preview) ? ' -resize 140x200!' : ' -resize 595x842!';

        $tmp_file_out = Horde_Util::getTempFile('fax_preview', true, '/tmp');
        /* Convert the page from the postscript file to PNG. */
        $command = sprintf('%s%s %s[%s] png:%s',
                           $this->_cmd['convert'],
                           $resize,
                           $tmp_file,
                           $page,
                           $tmp_file_out);
        Horde::logMessage('Executing command: ' . $command, 'DEBUG');
        exec($command);
        echo file_get_contents($tmp_file_out);
    }

    function getPDF()
    {
        $tmp_file = Horde_Util::getTempFile('fax', true, '/tmp');
        $fp = fopen($tmp_file, 'wb');
        fwrite($fp, $this->_data);
        fclose($fp);

        /* Convert the page from the postscript file to PDF. */
        $command = sprintf('%s %s -', $this->_cmd['ps2pdf'], $tmp_file);
        Horde::logMessage('Executing command: ' . $command, 'DEBUG');
        passthru($command);
    }

    /**
     * Attempts to return a concrete Hylax_Image instance based on $driver.
     *
     * @param string $driver  The type of concrete Hylax_Image subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Hylax_Image  The newly created concrete Hylax_Image instance, or
     *                      false on an error.
     * @throws Horde_Exception
     */
    function &factory($driver, $params = array())
    {
        $driver = basename($driver);
        include_once dirname(__FILE__) . '/Image/' . $driver . '.php';
        $class = 'Hylax_Image_' . $driver;
        if (class_exists($class)) {
            $image = &new $class($params);
            return $image;
        }

        throw new Horde_Exception(sprintf(_("No such backend \"%s\" found"), $driver));
        }
    }

    /**
     * Attempts to return a reference to a concrete Hylax_Image instance based
     * on $driver.
     *
     * It will only create a new instance if no Hylax_Image instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple image sources are required.
     *
     * This method must be invoked as: $var = &Hylax_Image::singleton()
     *
     * @param string $driver  The type of concrete Hylax_Image subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Hylax_Image instance, or false on
     *                error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Hylax_Image::factory($driver, $params);
        }

        return $instances[$signature];
    }

}
