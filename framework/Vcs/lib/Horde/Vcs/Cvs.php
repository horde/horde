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
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Cvs extends Horde_Vcs_Rcs
{
    /**
     * The current driver.
     *
     * @var string
     */
    protected $_driver = 'Cvs';

    /**
     * Driver features.
     *
     * @var array
     */
    protected $_features = array(
        'deleted'   => true,
        'patchsets' => true,
        'branches'  => true,
        'snapshots' => false);

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (!$this->getPath('cvsps')) {
            $this->_features['patchsets'] = false;
        }
    }

    /**
     * TODO
     */
    public function getFile($filename, $opts = array())
    {
        $filename = ltrim($filename, '/');
        $fname = $filename . ',v';

        /* Assume file is in the Attic if it doesn't exist. */
        if (!@is_file($this->sourceroot . '/' . $fname)) {
            $fname = dirname($filename) . '/Attic/' . basename($filename) . ',v';
        }

        if (!@is_file($this->sourceroot . '/' . $fname)) {
            throw new Horde_Vcs_Exception(sprintf('File "%s" not found', $filename));
        }

        return Horde_Vcs_Base::getFile($fname, $opts);
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
     * @param Horde_Vcs_File_Cvs $file  The desired file.
     * @param string $rev1              Original revision number to compare
     *                                  from.
     * @param string $rev2              New revision number to compare against.
     * @param array $opts               The following optional options:
     *                                  - 'num': (integer) DEFAULT: 3
     *                                  - 'type': (string) DEFAULT: 'unified'
     *                                  - 'ws': (boolean) DEFAULT: true
     *
     * @return string|boolean  False on failure, or a string containing the
     *                         diff on success.
     */
    protected function _diff(Horde_Vcs_File_Base $file, $rev1, $rev2, $opts)
    {
        $fullName = $file->getPath();
        $diff = array();
        $flags = '-kk ';

        if (!$opts['ws']) {
            $flags .= ' -bB ';
        }

        switch ($opts['type']) {
        case 'context':
            $flags .= '-p --context=' . escapeshellarg((int)$opts['num']);
            break;

        case 'unified':
            $flags .= '-p --unified=' . escapeshellarg((int)$opts['num']);
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
        $command = escapeshellcmd($this->getPath('rcsdiff')) . ' ' . $flags . ' -r' . escapeshellarg($rev1) . ' -r' . escapeshellarg($rev2) . ' ' . escapeshellarg($fullName) . ' 2>&1';
        if (VC_WINDOWS) {
            $command .= ' < ' . escapeshellarg(__FILE__);
        }

        exec($command, $diff, $retval);
        return ($retval > 0) ? $diff : array();
    }

    /**
     * TODO
     *
     * @throws Horde_Vcs_Exception
     */
    public function annotate($fileob, $rev)
    {
        $this->assertValidRevision($rev);

        $tmpfile = Horde_Util::getTempFile('vc', true, $this->_paths['temp']);
        $where = $fileob->getSourcerootPath();

        $pipe = popen(escapeshellcmd($this->getPath('cvs')) . ' -n server > ' . escapeshellarg($tmpfile), VC_WINDOWS ? 'wb' : 'w');

        $out = array(
            'Root ' . $this->sourceroot,
            'Valid-responses ok error Valid-requests Checked-in Updated Merged Removed M E',
            'UseUnchanged',
            'Argument -r',
            'Argument ' . $rev,
            'Argument ' . $where
        );

        $dirs = explode('/', dirname($where));
        while (count($dirs)) {
            $out[] = 'Directory ' . implode('/', $dirs);
            $out[] = $this->sourceroot . '/' . implode('/', $dirs);
            array_pop($dirs);
        }

        $out[] = 'Directory .';
        $out[] = $this->sourceroot;
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
     * @throws Horde_Vcs_Exception
     */
    public function checkout($fullname, $rev)
    {
        $this->assertValidRevision($rev);

        if (!($RCS = popen(escapeshellcmd($this->getPath('co')) . ' ' . escapeshellarg('-p' . $rev) . ' ' . escapeshellarg($fullname) . " 2>&1", VC_WINDOWS ? 'rb' : 'r'))) {
            throw new Horde_Vcs_Exception('Couldn\'t perform checkout of the requested file');
        }

        /* First line from co should be of the form :
         * /path/to/filename,v  -->  standard out
         * and we check that this is the case and error otherwise
         */
        $co = fgets($RCS, 1024);
        if (!preg_match('/^([\S ]+),v\s+-->\s+st(andar)?d ?out(put)?\s*$/', $co, $regs) ||
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
