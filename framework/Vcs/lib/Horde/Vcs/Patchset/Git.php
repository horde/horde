<?php
/**
 * Git patchset class.
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
class Horde_Vcs_Patchset_Git extends Horde_Vcs_Patchset_Base
{
    /**
     * Constructor
     *
     * @param Horde_Vcs $rep  A Horde_Vcs repository object.
     * @param array $opts     Additional options.
     * <pre>
     * 'file' - (string) The filename to produce patchsets for.
     * 'range' - (array) The patchsets to process.
     *           DEFAULT: None (all patchsets are processed).
     * </pre>
     */
    public function __construct($rep, $opts = array())
    {
        $revs = array();

        if (isset($opts['file'])) {
            $ob = $rep->getFile($opts['file']);
            $revs = $ob->getLog();
        } elseif (!empty($opts['range'])) {
            foreach ($opts['range'] as $val) {
                /* Grab a filename in the patchset to get log info. */
                list($resource, $stream) = $rep->runCommand('diff-tree --name-only -r ' . escapeshellarg($val));

                /* The first line is the SHA1 hash. */
                $ob = $rep->getFile(fgets($stream));
                fclose($stream);
                proc_close($resource);
                $revs[$val] = $ob->getLog($val);
            }
        }

        reset($revs);
        while (list($rev, $log) = each($revs)) {
            if (empty($log)) {
                continue;
            }

            $this->_patchsets[$rev] = array_merge(
                $log->toHash(),
                array('members' => array())
            );

            foreach ($log->getFiles() as $file) {
                $from = $log->getParent();
                $to = $rev;

                switch ($file['status']) {
                case 'A':
                    $status = Horde_Vcs_Patchset::ADDED;
                    break;

                case 'D':
                    $status = Horde_Vcs_Patchset::DELETED;
                    break;

                default:
                    $status = Horde_Vcs_Patchset::MODIFIED;
                }

                $statinfo = isset($file['added'])
                    ? array('added' => $file['added'], 'deleted' => $file['deleted'])
                    : array();

                $this->_patchsets[$rev]['members'][] = array_merge(array(
                    'file' => $file['srcPath'],
                    'from' => $from,
                    'status' => $status,
                    'to' => $to,
                ), $statinfo);
            }
        }
    }
}
