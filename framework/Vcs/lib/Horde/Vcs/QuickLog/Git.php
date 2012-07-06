<?php
/**
 * Git quick log class.
 *
 * Provides information for the most recent log entry of a file.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vcs
 */
class Horde_Vcs_QuickLog_Git extends Horde_Vcs_QuickLog_Base
{
    /**
     * Constructor.
     *
     * @param Horde_Vcs_Base $rep  A repository object.
     * @param string $rev          A log revision.
     * @param integer $date        A log timestamp.
     * @param string $author       A log author.
     * @param string $log          A log message.
     */
    public function __construct($rep, $rev, $date = null, $author = null,
                                $log = null)
    {
        parent::__construct($rep, $rev);

        $cmd = 'log --no-color --pretty=format:"%H%x00%an <%ae>%x00%at%x00%s%x00%b%n%x00" --no-abbrev -n 1 ' . escapeshellarg($this->_rev);
        list($resource, $pipe) = $this->_rep->runCommand($cmd);

        $log = '';
        while (!feof($pipe) && ($line = fgets($pipe)) && $line != "\0\n") {
            $log .= $line;
        }

        $fields = explode("\0", substr($log, 0, -1));
        fclose($pipe);
        proc_close($resource);
        if ($this->_rev != $fields[0]) {
            throw new Horde_Vcs_Exception(
                'Expected ' . $this->_rev . ', got ' . $fields[0]);
        }
        $this->_author = $fields[1];
        $this->_date = $fields[2];
        $this->_log = trim($fields[3] . "\n\n" . $fields[4]);
    }
}
