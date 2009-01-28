<?php
/**
 * Horde_Vcs_cvs implementation.
 *
 * Constructor args:
 * <pre>
 * 'sourceroot': The source root for this repository
 * 'paths': Hash with the locations of all necessary binaries: 'rcsdiff',
 *          'rlog', 'cvsps', 'cvsps_home', and 'temp' (the temp path).
 * </pre>
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Cvs extends Horde_Vcs_Rcs
{
    /**
     * Does driver support patchsets?
     *
     * @var boolean
     */
    protected $_patchsets = true;

    /**
     * Does driver support deleted files?
     *
     * @var boolean
     */
    protected $_deleted = true;

    /**
     * Does driver support branches?
     *
     * @var boolean
     */
    protected $_branches = true;

    /**
     * Returns the temporary file path.
     *
     * @return string  Temporary file path.
     */
    public function getTempPath()
    {
        return $this->_paths['temp'];
    }

    /**
     * TODO
     */
    public function isFile($where)
    {
        return @is_file($where . ',v') ||
               @is_file(dirname($where) . '/Attic/' . basename($where) . ',v');
    }

    /**
     * Obtain the differences between two revisions of a file.
     *
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $rev1          Original revision number to compare from.
     * @param string $rev2          New revision number to compare against.
     * @param array $opts           The following optional options:
     * <pre>
     * 'num' - (integer) DEFAULT: 3
     * 'type' - (string) DEFAULT: 'unified'
     * 'ws' - (boolean) DEFAULT: true
     * </pre>
     *
     * @return string|boolean  False on failure, or a string containing the
     *                         diff on success.
     */
    protected function _diff($file, $rev1, $rev2, $opts)
    {
        $fullName = $file->queryFullPath();
        $diff = array();
        $flags = '-kk ';

        if (!$opts['ws']) {
            $flags .= ' -bB ';
        }

        switch ($opts['type']) {
        case 'context':
            $flags .= '-p --context=' . (int)$opts['num'];
            break;

        case 'unified':
            $flags .= '-p --unified=' . (int)$opts['num'];
            break;

        case 'column':
            $flags .= '--side-by-side --width=120';
            break;

        case 'ed':
            $flags .= '-e';
            break;
        }

        // Windows versions of cvs always return $where with forwards slashes.
        if (VC_WINDOWS) {
            $fullName = str_replace(DIRECTORY_SEPARATOR, '/', $fullName);
        }

        // TODO: add options for $hr options - however these may not be
        // compatible with some diffs.
        $command = $this->getPath('rcsdiff') . " $flags -r" . escapeshellarg($rev1) . ' -r' . escapeshellarg($rev2) . ' ' . escapeshellarg($fullName) . ' 2>&1';
        if (VC_WINDOWS) {
            $command .= ' < "' . __FILE__ . '"';
        }

        exec($command, $diff, $retval);
        return ($retval > 0) ? $diff : array();
    }

    /**
     * TODO
     */
    public function getFileObject($filename, $opts = array())
    {
        if (substr($filename, 0, 1) != '/') {
            $filename = '/' . $filename;
        }

        $filename = $this->sourceroot() . $filename;

        /* Assume file is in the Attic if it doesn't exist. */
        $fname = $filename . ',v';
        if (!@is_file($fname)) {
            $fname = dirname($filename) . '/Attic/' . basename($filename) . ',v';
                                        }
        return parent::getFileObject($fname, $opts);
    }

    /**
     * TODO
     */
    public function annotate($fileob, $rev)
    {
        $this->assertValidRevision($rev);

        $tmpfile = Util::getTempFile('vc', true, $this->getTempPath());
        $where = $fileob->queryModulePath();

        $pipe = popen($this->getPath('cvs') . ' -n server > ' . escapeshellarg($tmpfile), VC_WINDOWS ? 'wb' : 'w');

        $out = array(
            'Root ' . $this->sourceroot(),
            'Valid-responses ok error Valid-requests Checked-in Updated Merged Removed M E',
            'UseUnchanged',
            'Argument -r',
            'Argument ' . $rev,
            'Argument ' . $where
        );

        $dirs = explode('/', dirname($where));
        while (count($dirs)) {
            $out[] = 'Directory ' . implode('/', $dirs);
            $out[] = $this->sourceroot() . '/' . implode('/', $dirs);
            array_pop($dirs);
        }

        $out[] = 'Directory .';
        $out[] = $this->sourceroot();
        $out[] = 'annotate';

        foreach ($out as $line) {
            fwrite($pipe, "$line\n");
        }
        pclose($pipe);

        if (!($fl = fopen($tmpfile, VC_WINDOWS ? 'rb' : 'r'))) {
            return false;
        }

        $lines = array();
        $line = fgets($fl, 4096);

        // Windows versions of cvs always return $where with forwards slashes.
        if (VC_WINDOWS) {
            $where = str_replace(DIRECTORY_SEPARATOR, '/', $where);
        }

        while ($line && !preg_match("|^E\s+Annotations for $where|", $line)) {
            $line = fgets($fl, 4096);
        }

        if (!$line) {
            throw new Horde_Vcs_Exception('Unable to annotate; server said: ' . $line);
        }

        $lineno = 1;
        while ($line = fgets($fl, 4096)) {
            if (preg_match('/^M\s+([\d\.]+)\s+\((.+)\s+(\d+-\w+-\d+)\):.(.*)$/', $line, $regs)) {
                $lines[] = array(
                    'rev' => $regs[1],
                    'author' => trim($regs[2]),
                    'date' => $regs[3],
                    'line' => $regs[4],
                    'lineno' => $lineno++
                );
            }
        }

        fclose($fl);
        return $lines;
    }

    /**
     * Returns a file pointing to the head of the requested revision of a
     * file.
     *
     * @param string $fullname  Fully qualified pathname of the desired file
     *                          to checkout.
     * @param string $rev       Revision number to check out.
     *
     * @return resource  A stream pointer to the head of the checkout.
     */
    public function checkout($fullname, $rev)
    {
        $this->assertValidRevision($rev);

        if (!($RCS = popen($this->getPath('co') . ' ' . escapeshellarg('-p' . $rev) . ' ' . escapeshellarg($fullname) . " 2>&1", VC_WINDOWS ? 'rb' : 'r'))) {
            throw new Horde_Vcs_Exception('Couldn\'t perform checkout of the requested file');
        }

        /* First line from co should be of the form :
         * /path/to/filename,v  -->  standard out
         * and we check that this is the case and error otherwise
         */
        $co = fgets($RCS, 1024);
        if (!preg_match('/^([\S ]+,v)\s+-->\s+st(andar)?d ?out(put)?\s*$/', $co, $regs) ||
            ($regs[1] != $fullname)) {
            throw new Horde_Vcs_Exception('Unexpected output from checkout: ' . $co);
        }

        /* Next line from co is of the form:
         * revision 1.2.3
         * TODO: compare this to $rev for consistency, atm we just
         *       discard the value to move input pointer along - avsm
         */
        $co = fgets($RCS, 1024);

        return $RCS;
    }

}

/**
 * Horde_Vcs_Cvs directory class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Cvs extends Horde_Vcs_Directory
{
    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository
     *
     * @param Horde_Vcs $rep  The Repository object this directory is part of.
     * @param string $dn      Path to the directory.
     * @param array $opts     TODO
     */
    public function __construct($rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);
        $this->_dirName = $rep->sourceroot() . '/' . $dn;

        /* Make sure we are trying to list a directory */
        if (!@is_dir($this->_dirName)) {
            throw new Horde_Vcs_Exception('Unable to find directory: ' . $this->_dirName);
        }

        /* Open the directory for reading its contents */
        if (!($DIR = @opendir($this->_dirName))) {
            throw new Horde_Vcs_Exception(empty($php_errormsg) ? 'Permission denied' : $php_errormsg);
        }

        /* Create two arrays - one of all the files, and the other of
         * all the directories. */
        while (($name = readdir($DIR)) !== false) {
            if (($name == '.') || ($name == '..')) {
                continue;
            }

            $path = $this->_dirName . '/' . $name;
            if (@is_dir($path)) {
                /* Skip Attic directory. */
                if ($name != 'Attic') {
                    $this->_dirs[] = $name;
                }
            } elseif (@is_file($path) && (substr($name, -2) == ',v')) {
                /* Spawn a new file object to represent this file. */
                $this->_files[] = $rep->getFileObject(substr($path, strlen($rep->sourceroot()), -2), array('quicklog' => !empty($opts['quicklog'])));
            }
        }

        /* Close the filehandle; we've now got a list of dirs and files. */
        closedir($DIR);

        /* If we want to merge the attic, add it in here. */
        if (!empty($opts['showattic'])) {
            try {
                $atticDir = new Horde_Vcs_Directory_Cvs($rep, $this->_moduleName . '/Attic', $opts, $this);
                $this->_atticFiles = $atticDir->queryFileList();
                $this->_mergedFiles = array_merge($this->_files, $this->_atticFiles);
            } catch (Horde_Vcs_Exception $e) {}
        }

        return true;
    }

}

/**
 * Horde_Vcs_Cvs file class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_File_Cvs extends Horde_Vcs_File
{
    /**
     * TODO
     */
    public $accum;

    /**
     * TODO
     */
    protected $_revsym = array();

    /**
     * Create a repository file object, and give it information about
     * what its parent directory and repository objects are.
     *
     * @param TODO $rep    TODO
     * @param string $fl   Full path to this file.
     * @param array $opts  TODO
     */
    public function __construct($rep, $fl, $opts = array())
    {
        parent::__construct($rep, $fl, $opts);

        /* Check that we are actually in the filesystem. */
        $file = $this->queryFullPath();
        if (!is_file($file)) {
            throw new Horde_Vcs_Exception('File Not Found: ' . $file);
        }

        $ret_array = array();
        $cmd = $rep->getPath('rlog') . ($this->_quicklog ? ' -r' : '') . ' ' .  escapeshellarg($file);
        exec($cmd, $ret_array, $retval);

        if ($retval) {
            throw new Horde_Vcs_Exception('Failed to spawn rlog to retrieve file log information for ' . $file);
        }

        $accum = $revsym = $symrev = array();
        $state = 'init';

        foreach ($ret_array as $line) {
            switch ($state) {
            case 'init':
                if (!strncmp('head: ', $line, 6)) {
                    $this->_head = $this->_branches['HEAD'] = substr($line, 6);
                } elseif (!strncmp('branch:', $line, 7)) {
                    $state = 'rev';
                }
                break;

            case 'rev':
                if (!strncmp('----------', $line, 10)) {
                    $state = 'info';
                    $this->_symrev = $symrev;
                    $this->_revsym = $revsym;
                } elseif (preg_match("/^\s+([^:]+):\s+([\d\.]+)/", $line, $regs)) {
                    // Check to see if this is a branch
                    if (preg_match('/^(\d+(\.\d+)+)\.0\.(\d+)$/', $regs[2])) {
                        $branchRev = $this->toBranch($regs[2]);
                        $this->_branches[$regs[1]] = $branchRev;
                    } else {
                        $symrev[$regs[1]] = $regs[2];
                        if (empty($revsym[$regs[2]])) {
                            $revsym[$regs[2]] = array();
                        }
                        $revsym[$regs[2]][] = $regs[1];
                    }
                }
                break;

            case 'info':
                if (strncmp('==============================', $line, 30) &&
                    strcmp('----------------------------', $line)) {
                    $accum[] = $line;
                } elseif (count($accum)) {
                    $this->accum = $accum;
                    $log = $rep->getLogObject($this, null);
                    $rev = $log->queryRevision();
                    $branch = $log->queryBranch();
                    if (empty($this->_branch) ||
                        in_array($this->_branch, $log->queryBranch()) ||
                        (($rep->cmp($rev, $this->_branches[$this->_branch]) === -1) &&
                         (empty($branch) ||
                          in_array('HEAD', $branch) ||
                          (strpos($this->_branches[$this->_branch], $rev) === 0)))) {
                        $log->setBranch($branch);
                        $this->_logs[$rev] = $log;
                        $this->_revs[] = $rev;
                    }
                    $accum = array();
                }
                break;
            }
        }

    }

    /**
     * If this file is present in an Attic directory, this indicates it.
     *
     * @return boolean  True if file is in the Attic, and false otherwise
     */
    public function isDeleted()
    {
        return (substr($this->_dir, -5) == 'Attic');
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return string  Filename without repository extension
     */
    public function queryName()
    {
        return preg_replace('/,v$/', '', $this->_name);
    }

    /**
     * Return the fully qualified filename of this object.
     *
     * @return Fully qualified filename of this object
     */
    public function queryFullPath()
    {
        return parent::queryModulePath();
    }

    /**
     * Return the name of this file relative to its sourceroot.
     *
     * @return string  Pathname relative to the sourceroot.
     */
    public function queryModulePath()
    {
        return preg_replace('|^'. $this->_rep->sourceroot() . '/?(.*),v$|', '\1', $this->queryFullPath());
    }

    /**
     * Given a revision number of the form x.y.0.z, this remaps it
     * into the appropriate branch number, which is x.y.z
     *
     * @param string $rev  Even-digit revision number of a branch
     *
     * @return string  Odd-digit Branch number
     */
    public function toBranch($rev)
    {
        /* Check if we have a valid revision number */
        if (!$this->_rep->isValidRevision($rev) ||
            (($end = strrpos($rev, '.')) === false)) {
            return false;
        }

        $rev[$end] = 0;
        if (($end2 = strrpos($rev, '.')) === false) {
            return substr($rev, ++$end);
        }

        return substr_replace($rev, '.', $end2, ($end - $end2 + 1));
    }

    /**
     * TODO
     */
    public function getBranchList()
    {
/*
// $trunk contains an array of trunk revisions.
$trunk = array();

// $branches is a hash with the branch revision as the key, and value
// being an array of revs on that branch.
$branches = array();

// Populate $col with a list of all the branch points.
foreach ($fl->branches as $rev) {
    $branches[$rev] = array();
}

// For every revision, figure out if it is a trunk revision, or
// instead associated with a branch.  If trunk, add it to the $trunk
// array.  Otherwise, add it to an array in $branches[$branch].
foreach ($fl->logs as $log) {
    $rev = $log->queryRevision();
    $baseRev = $rev_ob->strip($rev, 1);
    $branchFound = false;
    foreach ($fl->branches as $branch => $name) {
        if ($branch == $baseRev) {
            array_unshift($branches[$branch], $rev);
            $branchFound = true;
        }
    }
    // If its not a branch, then add it to the trunk.
    // TODO: this silently drops vendor branches atm! - avsm.
    if (!$branchFound && $rev_ob->sizeof($rev) == 2) {
        array_unshift($trunk, $rev);
    }
}

foreach ($branches as $col => $rows) {
    // If this branch has no actual commits on it, then it's a stub
    // branch, and we can remove it for this view.
    if (!sizeof($rows)) {
        unset($branches[$col]);
    }
}
*/
    }

    /**
     * TODO
     */
    public function queryRevsym($rev)
    {
        return isset($this->_revsym[$rev])
            ? $this->_revsym[$rev]
            : array();
    }

}

/**
 * Horde_Vcs_cvs Log class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Cvs extends Horde_Vcs_Log
{
    /**
     * Cached branch info.
     *
     * @var string
     */
    protected $_branch;

    /**
     * Constructor.
     */
    public function __construct($rep, $fl, $rev)
    {
        parent::__construct($rep, $fl, $rev);

        $raw = $fl->accum;

        /* Initialise a simple state machine to parse the output of rlog */
        $state = 'init';
        while (!empty($raw) && $state != 'done') {
            switch ($state) {
            /* Found filename, now looking for the revision number */
            case 'init':
                $line = array_shift($raw);
                if (preg_match("/revision (.+)$/", $line, $parts)) {
                    $this->_rev = $parts[1];
                    $state = 'date';
                }
                break;

            /* Found revision and filename, now looking for date */
            case 'date':
                $line = array_shift($raw);
                if (preg_match("|^date:\s+(\d+)[-/](\d+)[-/](\d+)\s+(\d+):(\d+):(\d+).*?;\s+author:\s+(.+);\s+state:\s+(\S+);(\s+lines:\s+([0-9\s+-]+))?|", $line, $parts)) {
                    $this->_date = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);
                    $this->_author = $parts[7];
                    $this->_state = $parts[8];
                    $this->_lines = isset($parts[10]) ? $parts[10] : '';
                    $state = 'branches';
                }
                break;

            /* Look for a branch point here - format is 'branches:
             * x.y.z; a.b.c;' */
            case 'branches':
                /* If we find a branch tag, process and pop it,
                   otherwise leave input stream untouched */
                if (!empty($raw) &&
                    preg_match("/^branches:\s+(.*)/", $raw[0], $br)) {
                    /* Get the list of branches from the string, and
                     * push valid revisions into the branches array */
                    $brs = preg_split('/;\s*/', $br[1]);
                    foreach ($brs as $brpoint) {
                        if ($rep->isValidRevision($brpoint)) {
                            $this->_branches[] = $brpoint;
                        }
                    }
                    array_shift($raw);
                }

                $state = 'done';
                break;
            }
        }

        /* Assume the rest of the lines are the log message */
        $this->_log = implode("\n", $raw);
        $this->_tags = $fl->queryRevsym($this->_rev);
    }

    /**
     * TODO
     */
    public function setBranch($branch)
    {
        $this->_branch = $branch;
    }

    /**
     * TODO
     */
    public function queryBranch()
    {
        if (!empty($this->_branch)) {
            return $this->_branch;
        }

        $branches = $this->_file->queryBranches();
        $key = array_keys($branches, $this->_rev);
        return empty($key)
            ? array_keys($branches, $this->_rep->strip($this->_rev, 1))
            : $key;
    }

}

/**
 * Horde_Vcs_Cvs Patchset class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Patchset_Cvs extends Horde_Vcs_Patchset
{
    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param string $file    The filename to create a patchset for.
     */
    public function __construct($rep, $file)
    {
        $file = $rep->sourceroot() . '/' . $file;

        /* Check that we are actually in the filesystem. */
        if (!$rep->isFile($file)) {
            throw new Horde_Vcs_Exception('File Not Found');
        }

        /* Call cvsps to retrieve all patchsets for this file. */
        $cvsps_home = $rep->getPath('cvsps_home');
        $HOME = !empty($cvsps_home) ?
            'HOME=' . $cvsps_home . ' ' :
            '';

        $ret_array = array();
        $cmd = $HOME . $rep->getPath('cvsps') . ' -u --cvs-direct --root ' . escapeshellarg($rep->sourceroot()) . ' -f ' . escapeshellarg(basename($file)) . ' ' . escapeshellarg(dirname($file));
        exec($cmd, $ret_array, $retval);
        if ($retval) {
            throw new Horde_Vcs_Exception('Failed to spawn cvsps to retrieve patchset information.');
        }

        $state = 'begin';
        reset($ret_array);
        while (list(,$line) = each($ret_array)) {
            $line = trim($line);

            if ($line == '---------------------') {
                $state = 'begin';
                continue;
            }

            switch ($state) {
            case 'begin':
                $id = str_replace('PatchSet ', '', $line);
                $this->_patchsets[$id] = array();
                $state = 'info';
                break;

            case 'info':
                $info = explode(':', $line, 2);
                $info[1] = ltrim($info[1]);

                switch ($info[0]) {
                case 'Date':
                    $d = new DateTime($info[1]);
                    $this->_patchsets[$id]['date'] = $d->format('U');
                    break;

                case 'Author':
                    $this->_patchsets[$id]['author'] = $info[1];
                    break;

                case 'Branch':
                    $this->_patchsets[$id]['branches'] = ($info[1] == 'HEAD')
                        ? array()
                        : array($info[1]);
                    break;

                case 'Tag':
                    $this->_patchsets[$id]['tags'] = ($info[1] == '(none)')
                        ? array()
                        : array($info[1]);
                    break;

                case 'Log':
                    $state = 'log';
                    $this->_patchsets[$id]['log'] = '';
                    break;
                }
                break;

            case 'log':
                if ($line == 'Members:') {
                    $state = 'members';
                    $this->_patchsets[$id]['log'] = rtrim($this->_patchsets[$id]['log']);
                    $this->_patchsets[$id]['members'] = array();
                } else {
                    $this->_patchsets[$id]['log'] .= $line . "\n";
                }
                break;

            case 'members':
                if (!empty($line)) {
                    $parts = explode(':', $line);
                    list($from, $to) = explode('->', $parts[1], 2);
                    $status = 0;

                    if ($from == 'INITIAL') {
                        $from = null;
                        $status = self::INITIAL;
                    } elseif (substr($to, -6) == '(DEAD)') {
                        $to = null;
                        $status = self::DEAD;
                    }

                    $this->_patchsets[$id]['members'][] = array(
                        'file' => $parts[0],
                        'from' => $from,
                        'status' => $status,
                        'to' => $to
                    );
                }
                break;
            }
        }
    }

}
