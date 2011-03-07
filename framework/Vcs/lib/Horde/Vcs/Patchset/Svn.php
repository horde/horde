<?php
/**
 * Horde_Vcs_Svn Patchset class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Vcs
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
