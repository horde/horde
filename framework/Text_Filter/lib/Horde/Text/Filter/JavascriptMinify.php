<?php
/**
 * This filter cleans up javascript output by running it through an
 * optimizer/compressor.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Text_Filter
 */
class Horde_Text_Filter_JavascriptMinify extends Horde_Text_Filter
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'java' => null,
        'yui' => null
    );

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        /* Are we using the YUI Compressor? */
        if (!empty($this->_params['yui']) &&
            !empty($this->_params['java'])) {
            return $this->_runYuiCompressor($text);
        }

        /* Use PHP-based minifier. */
        $jsmin = new Horde_Text_Filter_JavascriptMinify_JsMin($text);
        try {
            return $jsmin->minify();
        } catch (Exception $e) {
            return $text;
        }
    }

    /**
     * Passes javascript through YUI Compressor.
     *
     * @param string $text  The javascript text.
     *
     * @return string  The modified text.
     */
    protected function _runYuiCompressor($text)
    {
        if (!is_executable($this->_params['java']) ||
            !file_exists($this->_params['yui'])) {
            return $text;
        }

        $descspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open(escapeshellcmd($this->_params['java']) . ' -jar ' . escapeshellarg($this->_params['yui']) . ' --type js', $descspec, $pipes);

        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        $out = '';
        while (!feof($pipes[1])) {
            $out .= fread($pipes[1], 8192);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $out;
    }

}
