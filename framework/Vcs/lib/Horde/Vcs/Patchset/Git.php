<?php
/**
 * Horde_Vcs_Patchset_Git class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
 */
class Horde_Vcs_Patchset_Git extends Horde_Vcs_Patchset
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
            $ob = $rep->getFileObject($opts['file']);
            $revs = $ob->queryLogs();
        } elseif (!empty($opts['range'])) {
            foreach ($opts['range'] as $val) {
                /* Grab a filename in the patchset to get log info. */
                $cmd = $rep->getCommand() . ' diff-tree --name-only -r ' . escapeshellarg($val);
                exec($cmd, $output);

                /* The first line is the SHA1 hash. */
                $ob = $rep->getFileObject($output[1]);
                $revs[$val] = $ob->queryLogs($val);
            }
        }

        reset($revs);
        while (list($rev, $log) = each($revs)) {
            if (empty($log)) {
                continue;
            }

            $this->_patchsets[$rev] = array(
                'log' => $log,
                'members' => array(),
            );

            foreach ($log->queryFiles() as $file) {
                $from = $log->queryParent();
                $to = $rev;

                switch ($file['status']) {
                case 'A':
                    $status = self::ADDED;
                    break;

                case 'D':
                    $status = self::DELETED;
                    break;

                default:
                    $status = self::MODIFIED;
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
