<?php
/**
 * Horde_Vcs_Cvs Patchset class.
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
class Horde_Vcs_Patchset_Cvs extends Horde_Vcs_Patchset
{
    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param string $file    The filename to create a patchset for.
     * @param array $opts     Additional options.
     * <pre>
     * 'file' - (string) The filename to process.
     *          REQUIRED for this driver.
     * 'range' - (array) The patchsets to process.
     *           DEFAULT: None (all patchsets are processed).
     * </pre>
     *
     * @throws Horde_Vcs_Exception
     */
    public function __construct($rep, $opts = array())
    {
        $file = $rep->sourceroot() . '/' . $opts['file'];

        /* Check that we are actually in the filesystem. */
        if (!$rep->isFile($file)) {
            throw new Horde_Vcs_Exception('File Not Found');
        }

        /* Call cvsps to retrieve all patchsets for this file. */
        $cvsps_home = $rep->getPath('cvsps_home');
        $HOME = !empty($cvsps_home) ?
            'HOME=' . escapeshellarg($cvsps_home) . ' ' :
            '';

        $rangecmd = empty($opts['range'])
            ? ''
            : ' -s ' . escapeshellarg(implode(',', $opts['range']));

        $ret_array = array();
        $cmd = $HOME . escapeshellcmd($rep->getPath('cvsps')) . $rangecmd . ' -u --cvs-direct --root ' . escapeshellarg($rep->sourceroot()) . ' -f ' . escapeshellarg(basename($file)) . ' ' . escapeshellarg(dirname($file));
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
                    $status = self::MODIFIED;

                    if ($from == 'INITIAL') {
                        $from = null;
                        $status = self::ADDED;
                    } elseif (substr($to, -6) == '(DEAD)') {
                        $to = null;
                        $status = self::DELETED;
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