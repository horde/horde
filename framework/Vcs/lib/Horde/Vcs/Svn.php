<?php
/**
 * Horde_Vcs_Svn implementation.
 *
 * Constructor args:
 * <pre>
 * 'sourceroot': The source root for this repository
 * 'paths': Hash with the locations of all necessary binaries: 'svn', 'diff'
 * </pre>
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Svn extends Horde_Vcs
{
    /**
     * SVN username.
     *
     * @var string
     */
    protected $_username = '';

    /**
     * SVN password.
     *
     * @var string
     */
    protected $_password = '';

    /**
     * Does driver support patchsets?
     *
     * @var boolean
     */
    protected $_patchsets = true;

    /**
     * Constructor.
     *
     * @param array $params  Required parameters (see above).
     */
    public function __construct($params = array())
    {
        if (!empty($params['username'])) {
            $this->_username = $params['username'];
        }

        if (!empty($params['password'])) {
            $this->_password = $params['password'];
        }
        parent::__construct($params);
    }

    /**
     * TODO
     */
    public function getCommand()
    {
        $svnPath = $this->getPath('svn');
        $tempDir = isset($this->_paths['svn_home'])
            ? $this->_paths['svn_home']
            : Horde_Util::getTempDir();
        $command = $svnPath . ' --non-interactive --config-dir ' . $tempDir;

        if ($this->_username) {
            $command .= ' --username ' . $this->_username;
        }

        if ($this->_password) {
            $command .= ' --password ' . $this->_password;
        }

        return $command;
    }

    /**
     * TODO
     */
    public function annotate($fileob, $rev)
    {
        $this->assertValidRevision($rev);

        $command = $this->getCommand() . ' annotate -r ' . escapeshellarg('1:' . $rev) . ' ' . escapeshellarg($fileob->queryFullPath()) . ' 2>&1';
        $pipe = popen($command, 'r');
        if (!$pipe) {
            throw new Horde_Vcs_Exception('Failed to execute svn annotate: ' . $command);
        }

        $lines = array();
        $lineno = 1;

        while (!feof($pipe)) {
            $line = fgets($pipe, 4096);
            if (preg_match('/^\s+(\d+)\s+([\w\.]+)\s(.*)$/', $line, $regs)) {
                $lines[] = array(
                    'rev' => $regs[1],
                    'author' => trim($regs[2]),
                    'date' => '',
                    'line' => $regs[3],
                    'lineno' => $lineno++
                );
            }
        }

        pclose($pipe);
        return $lines;
    }

    /**
     * Function which returns a file pointing to the head of the requested
     * revision of a file.
     *
     * @param string $fullname  Fully qualified pathname of the desired file
     *                          to checkout
     * @param string $rev       Revision number to check out
     *
     * @return resource  A stream pointer to the head of the checkout.
     */
    public function checkout($fullname, $rev)
    {
        $this->assertValidRevision($rev);

        if ($RCS = popen($this->getCommand() . ' cat -r ' . escapeshellarg($rev) . ' ' . escapeshellarg($fullname) . ' 2>&1', VC_WINDOWS ? 'rb' : 'r')) {
            return $RCS;
        }

        throw new Horde_Vcs_Exception('Couldn\'t perform checkout of the requested file');
    }

    /**
     * TODO
     */
    public function isValidRevision($rev)
    {
        return $rev && is_numeric($rev);
    }

    /**
     * Create a range of revisions between two revision numbers.
     *
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $r1            The initial revision.
     * @param string $r2            The ending revision.
     *
     * @return array  The revision range, or empty if there is no straight
     *                line path between the revisions.
     */
    public function getRevisionRange($file, $r1, $r2)
    {
        // TODO
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
        $flags = '';

        if (!$opts['ws']) {
            $flags .= ' -bB ';
        }

        switch ($opts['type']) {
        case 'context':
            $flags .= '--context=' . (int)$opts['num'];
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

        // TODO: add options for $hr options - however these may not
        // be compatible with some diffs.
        $command = $this->getCommand() . " diff --diff-cmd " . $this->getPath('diff') . ' -r ' . escapeshellarg($rev1 . ':' . $rev2) . ' -x ' . escapeshellarg($flags) . ' ' . escapeshellarg($file->queryFullPath()) . ' 2>&1';

        exec($command, $diff, $retval);
        return $diff;
    }

}

/**
 * Horde_Vcs_Svn directory class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Svn extends Horde_Vcs_Directory
{
    /**
     * Create a Directory object to store information about the files in a
     * single directory in the repository.
     *
     * @param Horde_Vcs $rep  The Repository object this directory is part of.
     * @param string $dn      Path to the directory.
     * @param array $opts     TODO
     */
    public function __construct($rep, $dn, $opts = array())
    {
        parent::__construct($rep, $dn, $opts);

        $cmd = $rep->getCommand() . ' ls ' . escapeshellarg($rep->sourceroot() . $this->queryDir()) . ' 2>&1';

        $dir = popen($cmd, 'r');
        if (!$dir) {
            throw new Horde_Vcs_Exception('Failed to execute svn ls: ' . $cmd);
        }

        /* Create two arrays - one of all the files, and the other of
         * all the dirs. */
        $errors = array();
        while (!feof($dir)) {
            $line = chop(fgets($dir, 1024));
            if (!strlen($line)) {
                continue;
            }

            if (substr($line, 0, 4) == 'svn:') {
                $errors[] = $line;
            } elseif (substr($line, -1) == '/') {
                $this->_dirs[] = substr($line, 0, -1);
            } else {
                $this->_files[] = $rep->getFileObject($this->queryDir() . '/' . $line, array('quicklog' => !empty($opts['quicklog'])));
            }
        }

        pclose($dir);

        if (empty($errors)) {
            return true;
        }

        throw new Horde_Vcs_Exception(implode("\n", $errors));
    }

}

/**
 * Horde_Vcs_Svn file class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_File_Svn extends Horde_Vcs_File
{
    /**
     * @var resource
     */
    public $logpipe;

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

        /* This doesn't work; need to find another way to simply
         * request the most recent revision:
         *
         * $flag = $this->_quicklog ? '-r HEAD ' : '';
         */
        $cmd = $rep->getCommand() . ' log -v ' . escapeshellarg($this->queryFullPath()) . ' 2>&1';
        $pipe = popen($cmd, 'r');
        if (!$pipe) {
            throw new Horde_Vcs_Exception('Failed to execute svn log: ' . $cmd);
        }

        $header = fgets($pipe);
        if (!strspn($header, '-')) {
            throw new Horde_Vcs_Exception('Error executing svn log: ' . $header);
        }

        $this->logpipe = $pipe;

        while (!feof($pipe)) {
            try {
                $log = $rep->getLogObject($this, null);
                $rev = $log->queryRevision();
                $this->logs[$rev] = $log;
                $this->_revs[] = $rev;
            } catch (Horde_Vcs_Exception $e) {}

            if ($this->_quicklog) {
                break;
            }
        }

        pclose($pipe);
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v).
     *
     * @return string  Filename without repository extension.
     */
    public function queryName()
    {
        return preg_replace('/,v$/', '', $this->_name);
    }

}

/**
 * Horde_Vcs_Svn log class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Svn extends Horde_Vcs_Log
{
    /**
     * TODO
     */
    protected $_files = array();

    /**
     * Constructor.
     */
    public function __construct($rep, $fl, $rev)
    {
        parent::__construct($rep, $fl, $rev);

        $line = fgets($fl->logpipe);

        if (feof($fl->logpipe) || !$line) {
            throw new Horde_Vcs_Exception('No more data');
        }

        if (preg_match('/^r([0-9]*) \| (.*?) \| (.*) \(.*\) \| ([0-9]*) lines?$/', $line, $matches)) {
            $this->_rev = $matches[1];
            $this->_author = $matches[2];
            $this->_date = strtotime($matches[3]);
            $size = $matches[4];
        } else {
            throw new Horde_Vcs_Exception('SVN Error');
        }

        fgets($fl->logpipe);

        while (($line = trim(fgets($fl->logpipe))) != '') {
            $this->_files[] = $line;
        }

        for ($i = 0; $i != $size; ++$i) {
            $this->_log = $this->_log . chop(fgets($fl->logpipe)) . "\n";
        }

        $this->_log = chop($this->_log);
        fgets($fl->logpipe);
    }

    /**
     * TODO
     */
    public function queryFiles()
    {
        return $this->_files;
    }

}

/**
 * Horde_Vcs_Svn Patchset class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Patchset_Svn extends Horde_Vcs_Patchset
{
    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param string $file    The filename to create patchsets for.
     */
    public function __construct($rep, $opts = array())
    {
        // TODO: Allow access via 'range'
        $fileOb = $rep->getFileObject($opts['file']);

        foreach ($fileOb->logs as $rev => $log) {
            $this->_patchsets[$rev] = array(
                'author' => $log->queryAuthor(),
                'branch' => '',
                'date' => $log->queryDate(),
                'log' => $log->queryLog(),
                'members' => array(),
                'tag' => ''
            );

            foreach ($log->queryFiles() as $file) {
                $action = substr($file, 0, 1);
                $file = preg_replace('/.*?\s(.*?)(\s|$).*/', '\\1', $file);
                $to = $rev;
                $status = self::MODIFIED;
                if ($action == 'A') {
                    $from = null;
                    $status = self::ADDED;
                } elseif ($action == 'D') {
                    $from = $to;
                    $to = null;
                    $status = self::DELETED;
                } else {
                    // This technically isn't the previous revision,
                    // but it works for diffing purposes.
                    $from = $to - 1;
                }

                $this->_patchsets[$rev]['members'][] = array('file' => $file,
                                                             'from' => $from,
                                                             'to' => $to,
                                                             'status' => $status);
            }
        }

        return true;
    }

}
