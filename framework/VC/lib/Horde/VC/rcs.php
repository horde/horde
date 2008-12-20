<?php
/**
 * Horde_VC_rcs implementation.
 *
 * Copyright 2004-2007 Jeff Schwentner <jeffrey.schwentner@lmco.com>
 *
 * @author  Jeff Schwentner <jeffrey.schwentner@lmco.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_VC
 */
class Horde_VC_rcs extends Horde_VC
{
    /**
     * Checks an RCS file in with a specified change log.
     *
     * @param string $filepath    Location of file to check in.
     * @param string $message     Log of changes since last version.
     * @param string $user        The user name to use for the check in.
     * @param boolean $newBinary  Does the change involve binary data?
     *
     * @return string|object  The new revision number on success, or a
     *                        PEAR_Error object on failure.
     */
    public function ci($filepath, $message, $user = null, $newBinary = false)
    {
        if ($user) {
            putenv('LOGNAME=' . $user);
        } else {
            putenv('LOGNAME=guest');
        }

        $Q = VC_WINDOWS ? '"' : "'" ;

        $ci_cmd = $this->getPath('ci') . ' ' . $Q . $filepath . $Q.' 2>&1';
        $rcs_cmd = $this->getPath('rcs') . ' -i -kb ' . $Q . $filepath . $Q.' 2>&1';
        $output = '';

        $message_lines = explode("\n", $message);

        $pipe_def = array(0 => array("pipe", 'r'),
                          1 => array("pipe", 'w'));

        if ($newBinary) {
            $process = proc_open($rcs_cmd, $pipe_def, $pipes);
        } else {
            $process = proc_open($ci_cmd, $pipe_def, $pipes);
        }

        if (is_resource($process)) {
            foreach ($message_lines as $line) {
                if ($line == '.\n') {
                    $line = '. \n';
                }
                fwrite($pipes[0], $line);
            }

            fwrite($pipes[0], "\n.\n");
            fclose($pipes[0]);

            while (!feof($pipes[1])) {
                $output .= fread($pipes[1], 8192);
            }
            fclose($pipes[1]);
            proc_close($process);
        } else {
            return PEAR::raiseError('Failed to open pipe in ci()');
        }

        if ($newBinary) {
            exec($ci_cmd . ' 2>&1', $return_array, $retval);

            if ($retval) {
                return PEAR::raiseError("Unable to spawn ci on $filepath from ci()");
            } else {
                foreach ($return_array as $line) {
                    $output .= $line;
                }
            }
        }

        $rev_start = strpos($output, 'new revision: ');

        // If no new revision, see if this is an initial checkin.
        if ($rev_start === false) {
            $rev_start = strpos($output, 'initial revision: ');
            $rev_end = strpos($output, ' ', $rev_start);
        } else {
            $rev_end = strpos($output, ';', $rev_start);
        }

        if ($rev_start !== false && $rev_end !== false) {
            $rev_start += 14;
            return substr($output, $rev_start, $rev_end - $rev_start);
        } else {
            unlock($filepath);
            $temp_pos = strpos($output, 'file is unchanged');
            if ($temp_pos !== false) {
                return PEAR::raiseError('Check-in Failure: ' . basename($filepath) . ' has not been modified');
            } else {
                return PEAR::raiseError("Failed to checkin $filepath, $ci_cmd, $output");
            }
        }
    }

    /**
     * Checks the locks on a CVS/RCS file.
     *
     * @param string $filepath    Location of file.
     * @param string &$locked_by  Returns the username holding the lock.
     *
     * @return boolean|object  True on success, or a PEAR_Error on failure.
     */
    public function isLocked($filepath, &$locked_by)
    {
        $rlog_cmd  = $this->getPath('rlog');
        $rlog_flag = ' -L ';

        $Q = VC_WINDOWS ? '"' : "'";

        $cmd = $rlog_cmd . $rlog_flag . $Q . $filepath . $Q;

        exec($cmd.' 2>&1', $return_array, $retval);

        if ($retval) {
            return PEAR::raiseError("Unable to spawn rlog on $filepath from isLocked()");
        } else {
            $output = '';

            foreach ($return_array as $line) {
                $output .= $line;
            }

            $start_name = strpos($output, 'locked by: ');
            $end_name = strpos($output, ';', $start_name);

            if ($start_name !== false && $end_name !== false) {
                $start_name += 11;
                $locked_by = substr($output, $start_name, $end_name - $start_name);
                return true;
            }  elseif (strlen($output) == 0) {
                return false;
            } else {
                return PEAR::raiseError('Failure running rlog in isLocked()');
            }
        }
    }

    /**
     * Locks a CVS/RCS file.
     *
     * @param string $filepath  Location of file.
     * @param string $user      User name to lock the file with
     *
     * @return boolean|object  True on success, or a PEAR_Error on failure.
     */
    public function lock($filepath, $user = null)
    {
        // Get username for RCS tag.
        if ($user) {
            putenv('LOGNAME=' . $user);
        } else {
            putenv('LOGNAME=guest');
        }

        $rcs_cmd = $this->getPath('rcs');
        $rcs_flag = ' -l ';

        $Q = VC_WINDOWS ? '"' : "'" ;
        $cmd = $rcs_cmd . $rcs_flag . $Q . $filepath . $Q;
        exec($cmd.' 2>&1', $return_array, $retval);

        if ($retval) {
            return PEAR::raiseError('Failed to spawn rcs ("' . $cmd . '") on "' . $filepath . '" (returned ' . $retval . ')');
        } else {
            $output = '';
            foreach ($return_array as $line) {
                $output .= $line;
            }

            $locked_pos = strpos($output, 'locked');
            if ($locked_pos !== false) {
                return true;
            } else {
                return PEAR::raiseError('Failed to lock "' . $filepath . '" (Ran "' . $cmd . '", got return code ' . $retval . ', output: ' . $output . ')');
            }
        }
    }

    /**
     * Unlocks a CVS/RCS file.
     *
     * @param string $filepath  Location of file.
     * @param string $user      User name to unlock the file with
     *
     * @return boolean|object  True on success, or a PEAR_Error on failure.
     */
    public function unlock($filepath, $user = null)
    {
        // Get username for RCS tag.
        if ($user) {
            putenv('LOGNAME=' . $user);
        } else {
            putenv('LOGNAME=guest');
        }

        $rcs_cmd = $this->getPath('rcs');
        $rcs_flag = ' -u ';

        $Q = VC_WINDOWS ? '"' : "'" ;
        $cmd = $rcs_cmd . $rcs_flag . $Q . $filepath . $Q;
        exec($cmd . ' 2>&1', $return_array, $retval);

        if ($retval) {
            return PEAR::raiseError('Failed to spawn rcs ("' . $cmd . '") on "' . $filepath . '" (returned ' . $retval . ')');
        } else {
            $output = '';

            foreach ($return_array as $line) {
                $output .= $line;
            }

            $unlocked_pos = strpos($output, 'unlocked');

            if ($unlocked_pos !== false) {
                return true;
            } else {
                // Already unlocked.
                return true;
            }
        }
    }

}

class Horde_VC_Revision_rcs extends Horde_VC_Revision
{
    /**
     * Given a revision number, remove a given number of portions from
     * it. For example, if we remove 2 portions of 1.2.3.4, we are
     * left with 1.2.
     *
     * @param string $val      Input revision
     * @param integer $amount  Number of portions to strip
     *
     * @return string  Stripped revision number
     */
    public function strip($val, $amount = 1)
    {
        //@TODO This concept is broken beyond CVS
        //if (!Horde_VC_Revision::valid($val)) {
        //    return false;
        //}
        $pos = 0;
        while ($amount-- > 0 && ($pos = strrpos($val, '.')) !== false) {
            $val = substr($val, 0, $pos);
        }
        return $pos !== false ? $val : false;
    }

    /**
     * The size of a revision number is the number of portions it has.
     * For example, 1,2.3.4 is of size 4.
     *
     * @param string $val  Revision number to determine size of
     *
     * @return integer  Size of revision number
     */
    public function sizeof($val)
    {
        //@TODO This concept is broken beyond CVS
        //if (!Horde_VC_Revision::valid($val)) {
        //    return false;
        //}

        return (substr_count($val, '.') + 1);
    }

    /**
     * Given two revision numbers, this figures out which one is
     * greater than the other by stepping along the decimal points
     * until a difference is found, at which point a sign comparison
     * of the two is returned.
     *
     * @param string $rev1  Period delimited revision number
     * @param string $rev2  Second period delimited revision number
     *
     * @return integer  1 if the first is greater, -1 if the second if greater,
     *                  and 0 if they are equal
     */
    public function cmp($rev1, $rev2)
    {
        return version_compare($rev1, $rev2);
    }

    /**
     * Return the logical revision before this one. Normally, this
     * will be the revision minus one, but in the case of a new
     * branch, we strip off the last two decimal places to return the
     * original branch point.
     *
     * @param string $rev  Revision number to decrement.
     *
     * @return string|boolean  Revision number, or false if none could be
     *                         determined.
     */
    public function prev($rev)
    {
        $last_dot = strrpos($rev, '.');
        $val = substr($rev, ++$last_dot);

        if (--$val > 0) {
            return substr($rev, 0, $last_dot) . $val;
        } else {
            $last_dot--;
            while (--$last_dot) {
                if ($rev[$last_dot] == '.') {
                    return  substr($rev, 0, $last_dot);
                } elseif ($rev[$last_dot] == null) {
                    return false;
                }
            }
        }
    }
}
