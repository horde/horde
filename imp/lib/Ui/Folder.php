<?php
/**
 * This class provides a place to store common code shared among IMP's various
 * UI views for folder manipulation.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Folder
{
    /**
     * Download folder(s) into a MBOX file.
     *
     * @param array $flist  The folder list.
     * @param boolean $zip  Compress with zip?
     *
     * @throws Horde_Exception
     */
    public function downloadMbox($flist, $zip = false)
    {
        global $browser, $injector;

        $mbox = $this->generateMbox($flist);

        if ($zip) {
            $horde_compress = Horde_Compress::factory('zip');
            try {
                $data = $horde_compress->compress(array(array(
                    'data' => $mbox,
                    'name' => reset($flist) . '.mbox'
                )), array(
                    'stream' => true
                ));
                fclose($mbox);
            } catch (Horde_Exception $e) {
                fclose($mbox);
                throw $e;
            }

            $suffix = '.zip';
            $type = 'application/zip';
        } else {
            $data = $mbox;
            $suffix = '.mbox';
            $type = null;
        }

        fseek($data, 0, SEEK_END);
        $browser->downloadHeaders(reset($flist) . $suffix, $type, false, ftell($data));

        rewind($data);
        while (!feof($data)) {
            echo fread($data, 8192);
        }
        fclose($data);
        exit;
    }

    /**
     * Generates a string that can be saved out to an mbox format mailbox file
     * for a folder or set of folders, optionally including all subfolders of
     * the selected folders as well. All folders will be put into the same
     * string.
     *
     * @author Didi Rieder <adrieder@sbox.tugraz.at>
     *
     * @param array $folder_list  A list of folder names to generate a mbox
     *                            file for (UTF7-IMAP).
     *
     * @return resource  A stream resource containing the text of a mbox
     *                   format mailbox file.
     */
    public function generateMbox($folder_list)
    {
        $body = fopen('php://temp', 'r+');

        if (empty($folder_list)) {
            return $body;
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        foreach ($folder_list as $folder) {
            try {
                $status = $imp_imap->status($folder, Horde_Imap_Client::STATUS_MESSAGES);
            } catch (IMP_Imap_Exception $e) {
                continue;
            }

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->size();

            try {
                $size = $imp_imap->fetch($folder, $query, array(
                    'ids' => $imp_imap->getIdsOb(Horde_Imap_Client_Ids::ALL, true)
                ));
            } catch (IMP_Imap_Exception $e) {
                continue;
            }

            $curr_size = 0;
            $start = 1;
            $slices = array();

            /* Handle 5 MB chunks of data at a time. */
            for ($i = 1; $i <= $status['messages']; ++$i) {
                $curr_size += $size[$i]->getSize();
                if ($curr_size > 5242880) {
                    $slices[] = $imp_imap->getIdsOb(range($start, $i), true);
                    $curr_size = 0;
                    $start = $i + 1;
                }
            }

            if ($start <= $status['messages']) {
                $slices[] = $imp_imap->getIdsOb(range($start, $status['messages']), true);
            }

            unset($size);

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $query->imapDate();
            $query->fullText(array(
                'peek' => true
            ));

            foreach ($slices as $slice) {
                try {
                    $res = $imp_imap->fetch($folder, $query, array(
                        'ids' => $slice
                    ));
                } catch (IMP_Imap_Exception $e) {
                    continue;
                }

                reset($res);
                while (list(,$ptr) = each($res)) {
                    $from = '<>';
                    if ($from_env = $ptr->getEnvelope()->from) {
                        $ptr2 = reset($from_env);
                        if (!empty($ptr2['mailbox']) && !empty($ptr2['host'])) {
                            $from = $ptr2['mailbox']. '@' . $ptr2['host'];
                        }
                    }

                    /* We need this long command since some MUAs (e.g. pine)
                     * require a space in front of single digit days. */
                    $imap_date = $ptr->getImapDate();
                    $date = sprintf('%s %2s %s', $imap_date->format('D M'), $imap_date->format('j'), $imap_date->format('H:i:s Y'));
                    fwrite($body, 'From ' . $from . ' ' . $date . "\r\n");
                    stream_copy_to_stream($ptr->getFullMsg(true), $body);
                    fwrite($body, "\r\n");
                }
            }
        }

        return $body;
    }

    /**
     * Import a MBOX file into a mailbox.
     *
     * @param string $mbox       The mailbox name to import into.
     * @param string $form_name  The form field name that contains the MBOX
     *                           data.
     *
     * @return string  Notification message.
     * @throws Horde_Exception
     */
    public function importMbox($mbox, $form_name)
    {
        $GLOBALS['browser']->wasFileUploaded($form_name, _("mailbox file"));

        $mbox = IMP_Mailbox::get(Horde_String::convertCharset($mbox, 'UTF-8', 'UTF7-IMAP'));
        $res = $mbox->importMbox($_FILES[$form_name]['tmp_name'], $_FILES[$form_name]['type']);
        $mbox_name = basename(Horde_Util::dispelMagicQuotes($_FILES[$form_name]['name']));

        if ($res === false) {
            throw new IMP_Exception(sprintf(_("There was an error importing %s."), $mbox_name));
        }

        return sprintf(ngettext('Imported %d message from %s.', 'Imported %d messages from %s', $res), $res, $mbox_name);
    }

}
