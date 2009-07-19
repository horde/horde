<?php
/**
 * Implementation of the Quota API for servers where IMAP Quota is not
 * supported, but it appears in the servers messages log for the IMAP
 * server.
 *
 * Requires the following parameter settings in imp/servers.php:
 * <pre>
 * 'quota' => array(
 *     'driver' => 'logfile',
 *     'params' => array(
 *         'logfile' => '/path/to/log/file',
 *         'taillines' => 10,
 *         'FTPmail'   => 'FTP',
 *         'beginocc'  => 'usage = ',
 *         'midocc'    => ' of ',
 *         'endocc'    => ' bytes'
 *     )
 * );
 *
 * logfile - The path/to/filename of the log file to use.
 * taillines - The number of lines to look at in the tail of the logfile.
 * FTPmail - If you want to show what FTP space is available (IMAP folder)
 *           or what mail space is available (INBOX).
 *           Defines the search string to username:
 *             FTPmail to identify the line with QUOTA info.
 * beginocc - String that designates the characters before the usage
 *            number.
 * midocc - String between usage and total storage space.
 * endocc - String after the storage number.
 * </pre>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tim Gorter <email@teletechnics.co.nz>
 * @package IMP_Quota
 */
class IMP_Quota_Logfile extends IMP_Quota
{
    /**
     * Constructor
     *
     * @param array $params  Hash containing connection parameters.
     */
    protected function __construct($params = array())
    {
        parent::__construct(array_merge(array(
            'logfile' => '',
            'taillines' => 10,
            'FTPmail' => 'FTP',
            'beginocc' => 'usage = ',
            'midocc' => ' of ',
            'endocc' => ' bytes'
        ), $params));
    }

    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws Horde_Exception
     */
    public function getQuota()
    {
        if (!is_file($this->_params['logfile'])) {
            throw new Horde_Exception(_("Unable to retrieve quota"), 'horde.error');
        }

        $full = file($this->_params['logfile']);
        for (; $this->_params['taillines'] > 0; --$this->_params['taillines']) {
            $tail[] = $full[count($full) - $this->_params['taillines']];
        }

        $uname = $_SESSION['imp']['user'];
        $FTPmail = $this->_params['FTPmail'];
        $virtline = preg_grep("[$uname: $FTPmail]", $tail);
        $virtline = array_values($virtline);
        $usage = substr($virtline[0], strpos($virtline[0], $this->_params['beginocc']) + strlen($this->_params['beginocc']), strpos($virtline[0], $this->_params['midocc']));
        $storage  = substr($virtline[0], strpos($virtline[0], $this->_params['midocc']) + strlen($this->_params['midocc']), strpos($virtline[0], $this->_params['endocc']));

        return array('usage' => $usage, 'limit' => $storage);
    }

}
