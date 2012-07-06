<?php
/**
 * Git log class.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Log_Git extends Horde_Vcs_Log_Base
{
    /**
     * @var string
     */
    protected $_parent = null;

    protected function _init()
    {
        /* Get diff statistics. */
        $stats = array();
        list($resource, $stream) = $this->_rep->runCommand('diff-tree --root --numstat ' . escapeshellarg($this->_rev));

        // Skip the first entry (it is the revision number)
        fgets($stream);
        while (!feof($stream) && $line = trim(fgets($stream))) {
            $tmp = explode("\t", $line);
            $stats[$tmp[2]] = array_slice($tmp, 0, 2);
        }
        fclose($stream);
        proc_close($resource);

        // @TODO use Commit, CommitDate, and Merge properties
        $cmd = 'whatchanged -m --no-color --pretty=format:"%H%x00%P%x00%an <%ae>%x00%at%x00%d%x00%s%x00%b%n%x00" --no-abbrev -n 1 ' . escapeshellarg($this->_rev);
        list($resource, $pipe) = $this->_rep->runCommand($cmd);

        $log = '';
        while (!feof($pipe) && ($line = fgets($pipe)) && $line != "\0\n") {
            $log .= $line;
        }

        $fields = explode("\0", substr($log, 0, -1));
        if ($this->_rev != $fields[0]) {
            throw new Horde_Vcs_Exception(
                'Expected ' . $this->_rev . ', got ' . $fields[0]);
        }

        $this->_parent = $fields[1];
        $this->_author = $fields[2];
        $this->_date = $fields[3];
        if ($fields[4]) {
            $value = substr($fields[4], 1, -1);
            foreach (explode(',', $value) as $val) {
                $val = trim($val);
                if (strpos($val, 'refs/tags/') === 0) {
                    $this->_tags[] = substr($val, 10);
                }
            }
            if (!empty($this->_tags)) {
                sort($this->_tags);
            }
        }
        $this->_log = trim($fields[5] . "\n\n" . $fields[6]);

        // Build list of files in this revision. The format of these lines is
        // documented in the git diff-tree documentation:
        // http://www.kernel.org/pub/software/scm/git/docs/git-diff-tree.html
        // @TODO: More than 1 parent? For now, stop after the first parent.
        while (!feof($pipe) && $line = fgets($pipe)) {
            if ($line == "\n") {
                break;
            }
            if (!preg_match('/^:(\d+) (\d+) (\w+) (\w+) (.+)\t(.+)(\t(.+))?/', $line, $matches)) {
                throw new Horde_Vcs_Exception('Unknown log line format: ' . $line);
            }

            $statinfo = isset($stats[$matches[6]])
                ? array('added' => $stats[$matches[6]][0], 'deleted' => $stats[$matches[6]][1])
                : array();

            $this->_files[$matches[6]] = array_merge(array(
                'srcMode' => $matches[1],
                'dstMode' => $matches[2],
                'srcSha1' => $matches[3],
                'dstSha1' => $matches[4],
                'status' => $matches[5],
                'srcPath' => $matches[6],
                'dstPath' => isset($matches[7]) ? $matches[7] : ''
            ), $statinfo);
        }

        fclose($pipe);
        proc_close($resource);

        $this->_setSymbolicBranches();
        $this->_branch = $this->_file->getBranch($this->_rev);
    }

    /**
     * TODO
     */
    public function getHashForPath($path)
    {
        $this->_ensureInitialized();
        return $this->_files[$path]['dstSha1'];
    }

    /**
     * TODO
     */
    public function getParent()
    {
        return $this->_parent;
    }
}