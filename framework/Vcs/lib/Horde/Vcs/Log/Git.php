<?php
/**
 * Horde_Vcs_Git log class.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Git extends Horde_Vcs_Log
{
    /**
     * @var string
     */
    protected $_parent = null;

    protected function _init()
    {
        /* Get diff statistics. */
        $stats = array();
        $cmd = $this->_rep->getCommand() . ' diff-tree --numstat ' . escapeshellarg($this->_rev);
        exec($cmd, $output);

        reset($output);
        // Skip the first entry (it is the revision number)
        next($output);
        while (list(,$v) = each($output)) {
            $tmp = explode("\t", $v);
            $stats[$tmp[2]] = array_slice($tmp, 0, 2);
        }

        // @TODO use Commit, CommitDate, and Merge properties
        $cmd = $this->_rep->getCommand() . ' whatchanged --no-color --pretty=format:"Rev:%H%nParents:%P%nAuthor:%an <%ae>%nAuthorDate:%at%nRefs:%d%n%n%s%n%b" --no-abbrev -n 1 ' . escapeshellarg($this->_rev);
        $pipe = popen($cmd, 'r');
        if (!is_resource($pipe)) {
            throw new Horde_Vcs_Exception('Unable to run ' . $cmd . ': ' . error_get_last());
        }

        $lines = stream_get_contents($pipe);
        fclose($pipe);
        $lines = explode("\n", $lines);

        while (true) {
            $line = trim(next($lines));
            if (!strlen($line)) { break; }
            if (strpos($line, ':') === false) {
                throw new Horde_Vcs_Exception('Malformed log line: ' . $line);
            }

            list($key, $value) = explode(':', $line, 2);
            $value = trim($value);

            switch (trim($key)) {
            case 'Rev':
                if ($this->_rev != $value) {
                    throw new Horde_Vcs_Exception('Expected ' . $this->_rev . ', got ' . $value);
                }
                break;

            case 'Parents':
                // @TODO: More than 1 parent?
                $this->_parent = $value;
                break;

            case 'Author':
                $this->_author = $value;
                break;

            case 'AuthorDate':
                $this->_date = $value;
                break;

            case 'Refs':
                if ($value) {
                    $value = substr($value, 1, -1);
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
                break;
            }
        }

        $log = '';
        $line = next($lines);
        while ($line !== false && substr($line, 0, 1) != ':') {
            $log .= $line . "\n";
            $line = next($lines);
        }
        $this->_log = trim($log);

        // Build list of files in this revision. The format of these lines is
        // documented in the git diff-tree documentation:
        // http://www.kernel.org/pub/software/scm/git/docs/git-diff-tree.html
        while ($line) {
            preg_match('/:(\d+) (\d+) (\w+) (\w+) (.+)\t(.+)(\t(.+))?/', $line, $matches);

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

            $line = next($lines);
        }

        $this->_setSymbolicBranches();
        $this->_branch = $this->_file->queryBranch($this->_rev);
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
    public function queryParent()
    {
        return $this->_parent;
    }
}