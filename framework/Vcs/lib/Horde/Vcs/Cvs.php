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
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
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
     * Does this driver support the given feature?
     *
     * @return boolean  True if driver supports the given feature.
     */
    public function hasFeature($feature)
    {
        return (($feature != 'patchsets') || $this->getPath('cvsps'))
            ? parent::hasFeature($feature)
            : false;
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
     *
     * @throws Horde_Vcs_Exception
     */
    public function annotate($fileob, $rev)
    {
        $this->assertValidRevision($rev);

        $tmpfile = Horde_Util::getTempFile('vc', true, $this->_paths['temp']);
        $where = $fileob->queryModulePath();

        $pipe = popen(escapeshellcmd($this->getPath('cvs')) . ' -n server > ' . escapeshellarg($tmpfile), VC_WINDOWS ? 'wb' : 'w');

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
