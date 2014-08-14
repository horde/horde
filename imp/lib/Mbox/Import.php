<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Method to import MBOX data into a mailbox.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mbox_Import
{
    /**
     * Temporary data.
     *
     * @var array
     */
    protected $_import;

    /**
     * Import mailbox.
     *
     * @var IMP_Mailbox
     */
    protected $_mbox;

    /**
     * Import a MBOX file into a mailbox.
     *
     * @param string $mbox       The mailbox name to import into (UTF-8).
     * @param string $form_name  The form field name that contains the MBOX
     *                           data.
     *
     * @return string  Notification message.
     * @throws Horde_Exception
     */
    public function import($mbox, $form_name)
    {
        $GLOBALS['browser']->wasFileUploaded($form_name, _("mailbox file"));
        $this->_mbox = $mbox;

        $res = $this->_import($_FILES[$form_name]['tmp_name'], $_FILES[$form_name]['type']);
        $mbox_name = basename(Horde_Util::dispelMagicQuotes($_FILES[$form_name]['name']));

        if ($res === false) {
            throw new IMP_Exception(sprintf(_("There was an error importing %s."), $mbox_name));
        }

        return sprintf(ngettext('Imported %d message from %s.', 'Imported %d messages from %s', $res), $res, $mbox_name);
    }

    /**
     * Imports messages from a mbox (see RFC 4155) -or- a message source
     * (eml) file.
     *
     * @param string $fname  Filename containing the message data.
     * @param string $type   The MIME type of the message data.
     *
     * @return mixed  False (boolean) on fail or the number of messages
     *                imported (integer) on success.
     * @throws IMP_Exception
     */
    protected function _import($fname, $type)
    {
        if (!is_readable($fname)) {
            return false;
        }

        $fd = null;

        switch ($type) {
        case 'application/gzip':
        case 'application/x-gzip':
        case 'application/x-gzip-compressed':
            // No need to default to Horde_Compress because it uses zlib
            // also.
            if (in_array('compress.zlib', stream_get_wrappers())) {
                $fd = 'compress.zlib://' . $fname;
            }
            break;

        case 'application/x-bzip2':
        case 'application/x-bzip':
            if (in_array('compress.bzip2', stream_get_wrappers())) {
                $fd = 'compress.bzip2://' . $fname;
            }
            break;

        case 'application/zip':
        case 'application/x-compressed':
        case 'application/x-zip-compressed':
            if (in_array('zip', stream_get_wrappers())) {
                $fd = 'zip://' . $fname;
            } else {
                try {
                    $zip = Horde_Compress::factory('Zip');
                    if ($zip->canDecompress) {
                        $file_data = file_get_contents($fname);

                        $zip_info = $zip->decompress($file_data, array(
                            'action' => Horde_Compress_Zip::ZIP_LIST
                        ));

                        if (!empty($zip_info)) {
                            $fd = fopen('php://temp', 'r+');

                            foreach (array_keys($zip_info) as $key) {
                                fwrite($fd, $zip->decompress($file_data, array(
                                    'action' => Horde_Compress_Zip::ZIP_DATA,
                                    'info' => $zip_info,
                                    'key' => $key
                                )));
                            }

                            rewind($fd);
                        }
                    }
                } catch (Horde_Compress_Exception $e) {
                    if ($fd) {
                        fclose($fd);
                        $fd = null;
                    }
                }
            }
            break;

        default:
            $fd = $fname;
            break;
        }

        if (is_null($fd)) {
            throw new IMP_Exception(_("The uploaded file cannot be opened."));
        }

        $parsed = new Horde_Mail_Mbox_Parse(
            $fd,
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->config->import_limit
        );

        $this->_import = array(
            'data' => array(),
            'msgs' => 0,
            'size' => 0
        );

        if ($pcount = count($parsed)) {
            foreach ($parsed as $key => $val) {
                $this->_importHelper($val, ($key + 1) != $pcount);
            }
        } else {
            $this->_importHelper($parsed[0]);
        }

        return $this->_import['msgs']
            ? $this->_import['msgs']
            : false;
    }

    /**
     * Helper for _import().
     *
     * @param array $msg       Message data.
     * @param integer $buffer  Buffer messages before sending?
     */
    protected function _importHelper($msg, $buffer = false)
    {
        $this->_import['data'][] = array_filter(array(
            'data' => $msg['data'],
            'internaldate' => $msg['date']
        ));
        $this->_import['size'] += $msg['size'];

        /* Buffer 1 MB of messages before sending. */
        if ($buffer && ($this->_import['size'] < 1048576)) {
            return;
        }

        try {
            $this->_mbox->imp_imap->append($this->_mbox, $this->_import['data']);
            $this->_import['msgs'] += count($this->_import['data']);
        } catch (IMP_Imap_Exception $e) {
            throw new IMP_Exception(sprintf(_("Error when importing messages; %u messages successfully imported before error."), $this->_import['msgs']));
        }

        foreach ($this->_import['data'] as $val) {
            fclose($val['data']);
        }

        $this->_import['data'] = array();
        $this->_import['size'] = 0;
    }

}
