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
        $this->_rep->assertValidRevision($rev);

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
