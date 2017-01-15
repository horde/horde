<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */

/**
 * Class that provides common function for running a command line program.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */
class Horde_JavascriptMinify_Util_Cmdline
{
    /**
     * Runs the compression command and returns the output.
     *
     * @param string $text           The javascript text.
     * @param string $cmd            Command.
     * @param Horde_Log_Logger $log  Logging object.
     *
     * @return string  The compressed javascript.
     */
    public function runCmd($text, $cmd, Horde_Log_Logger $log)
    {
        $descspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open($cmd, $descspec, $pipes);

        fwrite($pipes[0], $text);
        fclose($pipes[0]);

        $out = '';
        while (!feof($pipes[1])) {
            $out .= fread($pipes[1], 8192);
        }

        $error = '';
        while (!feof($pipes[2])) {
            $error .= fread($pipes[2], 8192);
        }
        if (strlen($error)) {
            $log->log(
                sprintf('Output from %s: %s', $cmd, $error),
                'WARN'
            );
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $out;
    }

}
