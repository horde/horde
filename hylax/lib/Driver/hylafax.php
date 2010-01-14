<?php
/**
 * Hylax_Driver_hylafax Class
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Hylax
 */
class Hylax_Driver_hylafax extends Hylax_Driver {

    var $_states = array();
    var $_stat_cols = array();
    var $_cmd = array();

    public function __construct($params)
    {
        parent::__construct($params);

        $this->_states = Hylax::getStates();
        $this->_stat_cols = Hylax::getStatCols();
        $this->_cmd = array('sendfax' => '/usr/bin/sendfax');
    }

    public function send($number, $data, $time = null)
    {
        $command = sprintf('%s -n -d %s',
                           $this->_cmd['sendfax'],
                           $number);
        $descriptorspec = array(0 => array("pipe", "r"),
                                1 => array("pipe", "w"),
                                2 => array("pipe", "w"));

        /* Set up the process. */
        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fwrite($pipes[0], $data);
            fclose($pipes[0]);

            $output = '';
            while (!feof($pipes[1])) {
                $output .= fgets($pipes[1], 1024);
            }
            fclose($pipes[1]);

            proc_close($process);
        }

        /* Regex match the job id from the output. */
        preg_match('/request id is (\d+)/', $output, $matches);
        if (isset($matches[1])) {
            return $matches[1];
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
        switch ($folder) {
        case 'inbox':
            return $this->_parseFaxStat($this->_exec('faxstat -r'));
            break;

        case 'outbox':
            return $this->_parseFaxStat($this->_exec('faxstat -s'));
            break;

        case 'sent':
            return $this->_parseFaxStat($this->_exec('faxstat -d'));
            break;

        case 'archive':
            //return $GLOBALS['storage']->getFolder($path);
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
            $filename = '/var/spool/fax/sendq/q' . $job_id;
            $job = $this->_getParseSendJob($filename);
            break;

        case 'sent':
            $filename = '/var/spool/fax/doneq/q' . $job_id;
            $job = $this->_getParseSendJob($filename);
            break;

        case 'archive':
            //return $GLOBALS['storage']->getFolder($path);
            break;
        }

        $job['thumbs'] = $this->getThumbs($job_id, 'docq/' . $job['postscript'], true);

        return $job;
    }

    public function getStatus($job_id)
    {
        static $send_q = array();
        static $done_q = array();
        if (empty($send_q)) {
            exec('/usr/bin/faxstat -s', $output);
            $iMax = count($output);
            for ($i = 4; $i < $iMax; $i++) {
                $send_q[] = $output[$i];
            }
        }
        if (empty($done_q)) {
            exec('/usr/bin/faxstat -d', $output);
            $iMax = count($output);
            for ($i = 4; $i < $iMax; $i++) {
                $done_q[] = $output[$i];
            }
        }

        /* Check the queues. */
        foreach ($send_q as $line) {
            if ((int)substr($line, 0, 4) == $job_id) {
                return _("Sending");
            }
        }
        foreach ($done_q as $line) {
            if ((int)substr($line, 0, 4) == $job_id) {
                return substr($line, 51);
            }
        }
        return '';
    }

    protected function _getParseSendJob($filename)
    {
        $job = array();
        $job_file = file_get_contents($filename);
        $job_file = explode("\n", $job_file);
        foreach ($job_file as $line) {
            if (empty($line)) {
                continue;
            }
            list($key, $value) = explode(':', $line, 2);
            if ($key == 'postscript') {
                $job[$key] = basename($value);
            } else {
                $job[$key] = $value;
            }
        }
        return $job;
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

    protected function _parseFaxStat($result)
    {
        $out = array();
        $i = 0;
        foreach ($result as $line) {
            /* Job ID number expected as first char. */
            if (!empty($line) && is_numeric($line[0])) {
                $values = explode('|', $line);
                foreach ($this->_stat_cols as $j => $key) {
                    $out[$i][$key] = $values[$j];
                }
                $out[$i]['state'] = $this->_states[$out[$i]['state']];
            }
            $i++;
        }
        return $out;
    }

}
