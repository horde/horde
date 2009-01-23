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
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
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
        parent::__construct();
    }

    /**
     * TODO
     */
    public function getCommand()
    {
        $svnPath = $this->getPath('svn');
        $tempDir = isset($this->_paths['svn_home'])
            ? $this->_paths['svn_home']
            : Util::getTempDir();
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
        $rep->assertValidRevision($rev);

        return ($RCS = popen($this->getCommand() . ' cat -r ' . escapeshellarg($rev) . ' ' . escapeshellarg($fullname) . ' 2>&1', VC_WINDOWS ? 'rb' : 'r'))
            ? $RCS
            : throw new Horde_Vcs_Exception('Couldn\'t perform checkout of the requested file');
    }

    /**
     * TODO
     */
    public function isValidRevision($rev)
    {
        return $rev && is_numeric($rev);
    }

}

/**
 * Horde_Vcs_Svn diff class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Diff_Svn extends Horde_Vcs_Diff
{
    /**
     * Obtain the differences between two revisions of a file.
     *
     * @param Horde_Vcs $rep        A repository object.
     * @param Horde_Vcs_File $file  The desired file.
     * @param string $rev1          Original revision number to compare from.
     * @param string $rev2          New revision number to compare against.
     * @param string $type          The type of diff (e.g. 'unified').
     * @param integer $num          Number of lines to be used in context and
     *                              unified diffs.
     * @param boolean $ws           Show whitespace in the diff?
     *
     * @return string|boolean  False on failure, or a string containing the
     *                         diff on success.
     */
    public function get($rep, $file, $rev1, $rev2, $type = 'context',
                        $num = 3, $ws = true)
    {
        /* Check that the revision numbers are valid */
        $rev1 = $rep->isValidRevision($rev1) ? $rev1 : 0;
        $rev2 = $rep->isValidRevision($rev1) ? $rev2 : 0;

        $fullName = $file->queryFullPath();
        $diff = array();
        $options = '';
        if (!$ws) {
            $options .= ' -bB ';
        }

        switch ($type) {
        case 'context':
            $options .= '--context=' . (int)$num;
            break;

        case 'unified':
            $options .= '-p --unified=' . (int)$num;
            break;

        case 'column':
            $options .= '--side-by-side --width=120';
            break;

        case 'ed':
            $options .= '-e';
            break;
        }

        // TODO: add options for $hr options - however these may not
        // be compatible with some diffs.
        $command = $rep->getCommand() . " diff --diff-cmd " . $rep->getPath('diff') . " -r $rev1:$rev2 -x " . escapeshellarg($options) . ' ' . escapeshellarg($file->queryFullPath()) . ' 2>&1';

        exec($command, $diff, $retval);
        return $diff;
    }

}

/**
 * Horde_Vcs_Svn directory class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Directory_Svn extends Horde_Vcs_Directory
{
    /**
     * Tell the object to open and browse its current directory, and
     * retrieve a list of all the objects in there.  It then populates
     * the file/directory stack and makes it available for retrieval.
     *
     * @return integer  1 on success.
     */
    public function browseDir($cache = null, $quicklog = true,
                              $showattic = false)
    {
        $cmd = $this->_rep->getCommand() . ' ls ' . escapeshellarg($this->_rep->sourceroot() . $this->queryDir()) . ' 2>&1';

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
                $this->_files[] = $this->_rep->getFileObject($this->queryDir() . "/$line", array('cache' => $cache, 'quicklog' => $quicklog));
            }
        }

        pclose($dir);

        return $errors
            ? throw new Horde_Vcs_Exception(implode("\n", $errors))
            : true;
    }

}

/**
 * Horde_Vcs_Svn file class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_File_Svn extends Horde_Vcs_File {

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
        $this->rep = $rep;
        $this->name = basename($fl);
        $this->dir = dirname($fl);
        $this->filename = $fl;
        $this->quicklog = !empty($opts['quicklog']);

        /* This doesn't work; need to find another way to simply
         * request the most recent revision:
         *
         * $flag = $this->quicklog ? '-r HEAD ' : ''; */
        $flag = '';
        $cmd = $this->rep->getCommand() . ' log -v ' . $flag . escapeshellarg($this->queryFullPath()) . ' 2>&1';
        $pipe = popen($cmd, 'r');
        if (!$pipe) {
            throw new Horde_Vcs_Exception('Failed to execute svn log: ' . $cmd);
        }

        $header = fgets($pipe);
        if (!strspn($header, '-')) {
            throw new Horde_Vcs_Exception('Error executing svn log: ' . $header);
        }

        while (!feof($pipe)) {
            $log = new Horde_Vcs_Log_Svn($this->rep, $this);
            $err = $log->processLog($pipe);
            if ($err) {
                $rev = $log->queryRevision();
                $this->logs[$rev] = $log;
                $this->revs[] = $rev;
            }

            if ($this->quicklog) {
                break;
            }
        }

        pclose($pipe);
    }

    /**
     * Returns name of the current file without the repository
     * extensions (usually ,v)
     *
     * @return Filename without repository extension
     */
    function queryName()
    {
       return preg_replace('/,v$/', '', $this->name);
    }

}

/**
 * Horde_Vcs_Svn log class.
 *
 * Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Log_Svn extends Horde_Vcs_Log
{
    public $err;
    public $files;

    public function processLog($pipe)
    {
        $line = fgets($pipe);

        if (feof($pipe)) {
            return false;
        }

        if (preg_match('/^r([0-9]*) \| (.*?) \| (.*) \(.*\) \| ([0-9]*) lines?$/', $line, $matches)) {
            $this->rev = $matches[1];
            $this->author = $matches[2];
            $this->date = strtotime($matches[3]);
            $size = $matches[4];
        } else {
            $this->err = $line;
            return false;
        }

        fgets($pipe);

        $this->files = array();
        while (($line = trim(fgets($pipe))) != '') {
            $this->files[] = $line;
        }

        for ($i = 0; $i != $size; ++$i) {
            $this->log = $this->log . chop(fgets($pipe)) . "\n";
        }

        $this->log = chop($this->log);
        fgets($pipe);

        return true;
    }

}

/**
 * Horde_Vcs_Svn Patchset class.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Vcs
 */
class Horde_Vcs_Patchset_Svn extends Horde_Vcs_Patchset
{
    /**
     * Populate the object with information about the patchsets that
     * this file is involved in.
     *
     * @return boolean  True on success.
     */
    function getPatchsets()
    {
        $fileOb = new Horde_Vcs_File_Svn($this->_rep, $this->_file);

        $this->_patchsets = array();
        foreach ($fileOb->logs as $rev => $log) {
            $this->_patchsets[$rev] = array();
            $this->_patchsets[$rev]['date'] = $log->queryDate();
            $this->_patchsets[$rev]['author'] = $log->queryAuthor();
            $this->_patchsets[$rev]['branch'] = '';
            $this->_patchsets[$rev]['tag'] = '';
            $this->_patchsets[$rev]['log'] = $log->queryLog();
            $this->_patchsets[$rev]['members'] = array();
            foreach ($log->files as $file) {
                $action = substr($file, 0, 1);
                $file = preg_replace('/.*?\s(.*?)(\s|$).*/', '\\1', $file);
                $to = $rev;
                if ($action == 'A') {
                    $from = 'INITIAL';
                } elseif ($action == 'D') {
                    $from = $to;
                    $to = '(DEAD)';
                } else {
                    // This technically isn't the previous revision,
                    // but it works for diffing purposes.
                    $from = $to - 1;
                }

                $this->_patchsets[$rev]['members'][] = array('file' => $file,
                                                             'from' => $from,
                                                             'to' => $to);
            }
        }

        return true;
    }

}
