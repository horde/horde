<?php
/**
 * Hylax_Driver_spandsp Class
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Hylax
 */
class Hylax_Driver_spandsp extends Hylax_Driver {

    protected $_states = array();
    protected $_stat_cols = array();
    protected $_cmd = array();

    public function __construct($params)
    {
        parent::__construct($params);

        $this->_states = Hylax::getStates();
        $this->_stat_cols = Hylax::getStatCols();
    }

    public function send($number, $data, $time = null)
    {
        /* Create a temporary file. */
        $filename = sprintf("%s.fax", Horde::getTempFile('hylax'));
        $fh = fopen($filename, "w");
        fwrite($fh, $data);
        fclose($fh);

        return $this->createCallFile($filename);
    }

    public function createCallFile($filename)
    {
        global $conf;

        /* Create outgoing call */
        $data  = sprintf("Channel: %s/%s\n", $conf['fax']['params']['channel'], $number);
        $data .= sprintf("MaxRetries: %d\n", $conf['fax']['params']['maxretries']);
        $data .= sprintf("RetryTime: %d\n", $conf['fax']['params']['retrytime']);
        $data .= sprintf("WaitTime: %d\n", $conf['fax']['params']['waittime']);
        $data .= sprintf("Application: %s\n", 'txfax');
        $data .= sprintf("Data: %s|caller\n", $filename);

        $outfile = sprintf("%s/%s.call", $conf['fax']['params']['outgoing'], strval(new Horde_Support_Uuid()));
        if ($fh = fopen($outfile, "w")) {
            fwrite($fh, $data);
            fclose($fh);
            return true;
        }

        return PEAR::raiseError(sprintf(_("Could not send fax. %s"), $output));
    }

    public function numFaxesIn()
    {
        //$inbox = $this->getInbox();
        //return count($inbox);
    }

    public function numFaxesOut()
    {
        //$outbox = $this->getOutbox();
        //return count($outbox);
    }

    public function getFolder($folder, $path = null)
    {
        // FIXME: This method is intended to return an array of items in the
        // specified folder.
        // Need to figure out how to make this work with SpanDSP.
        switch ($folder) {
        case 'inbox':
            return array();
            break;

        case 'outbox':
            return array();
            break;

        case 'sent':
            return array();
            break;

        case 'archive':
            //return $GLOBALS['storage']->getFolder($path);
            return array();
            break;
        }
    }

    public function getJob($job_id, $folder, $path = null)
    {
        global $conf;

        $job = array();

        switch ($folder) {
        case 'inbox':
            break;

        case 'outbox':
            // $filename = '/var/spool/fax/sendq/q' . $job_id;
            // $job = $this->_getParseSendJob($filename);
            break;

        case 'sent':
            // $filename = '/var/spool/fax/doneq/q' . $job_id;
            // $job = $this->_getParseSendJob($filename);
            break;

        case 'archive':
            //return $GLOBALS['storage']->getFolder($path);
            break;
        }

        // $job['thumbs'] = $this->getThumbs($job_id, 'docq/' . $job['postscript'], true);

        return $job;
    }

    public function getStatus($job_id)
    {
	return null;
    }

    public function getThumbs($job_id, $ps)
    {
        if ($this->_vfs->exists(HYLAX_VFS_PATH, $job_id)) {
            /* Return thumb image list. */
            $images = $this->_vfs->listFolder(HYLAX_VFS_PATH . '/' . $job_id, 'doc.png');
            if (!empty($images)) {
                return array_keys($images);
            }
        }
        $images = $this->imagesToVFS($job_id, $ps);
        return array_keys($images);
    }

    public function imagesToVFS($job_id, $ps)
    {
        global $conf;

        $this->_vfs->autoCreatePath(HYLAX_VFS_PATH . '/' . $job_id);

        $ps = '/var/spool/fax/' . $ps;
        $vfs_path = $conf['vfs']['params']['vfsroot'] . '/' . HYLAX_VFS_PATH;
        /* Do thumbs. */
        $cmd = sprintf('convert -geometry 25%% %s %s/%s/thumb.png', $ps, $vfs_path, $job_id);
        $result = $this->_exec($cmd);
        /* Do full images. */
        $cmd = sprintf('convert %s %s/%s/doc.png', $ps, $vfs_path, $job_id);
        $result = $this->_exec($cmd);

        /* Return thumb image list. */
        return $this->_vfs->listFolder(HYLAX_VFS_PATH . '/' . $job_id, 'doc.png');
    }

    protected function _exec($cmd, $input = '')
    {
        $spec = array(//0 => array('pipe', 'r'),
                      1 => array('pipe', 'w'),
                      2 => array('file', '/tmp/error-output.txt', 'a')
                      );
        $proc = proc_open($this->_params['base_path'] . $cmd, $spec, $pipes);
        //fwrite($pipes[0], $input);
        //@fclose($pipes[0]);
        while (!feof($pipes[1])) {
            $result[] = trim(fgets($pipes[1], 1024));
        }
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        proc_close($proc);

        if (empty($result[(count($result) - 1)])) {
             unset($result[(count($result) - 1)]);
        }

        return $result;
    }

}
