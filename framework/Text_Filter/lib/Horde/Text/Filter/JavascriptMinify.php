<?php
/**
 * This filter cleans up javascript output by running it through an
 * optimizer/compressor.
 *
 * Copyright 2009-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @deprecated Use Horde_JavascriptMinify package instead.
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 */
class Horde_Text_Filter_JavascriptMinify extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'closure' => null,
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
        if (!empty($this->_params['java'])) {
            /* Are we using the YUI Compressor? */
            if (!empty($this->_params['yui'])) {
                return $this->_runCompressor($text, $this->_params['yui'], ' --type js');
            }

            /* Are we using the Google Closure Compiler? */
            if (!empty($this->_params['closure'])) {
                return $this->_runCompressor($text, $this->_params['closure']);
            }
        }

        /* Use PHP-based minifier. */
        if (class_exists('Horde_Text_Filter_Jsmin')) {
            $jsmin = new Horde_Text_Filter_Jsmin($text);
            try {
                return $jsmin->minify();
            } catch (Exception $e) {}
        }

        return $text;
    }

    /**
     * Passes javascript through a java compressor (YUI or Closure).
     *
     * @param string $text  The javascript text.
     * @param string $jar   The JAR location.
     * @param string $args  Additional command line arguments.
     *
     * @return string  The modified text.
     */
    protected function _runCompressor($text, $jar, $args = '')
    {
        if (!is_executable($this->_params['java']) ||
            !file_exists($jar)) {
            return $text;
        }

        $descspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open(escapeshellcmd($this->_params['java']) . ' -jar ' . escapeshellarg($jar) . $args, $descspec, $pipes);

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
