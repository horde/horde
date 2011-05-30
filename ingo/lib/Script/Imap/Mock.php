<?php
/**
 * TODO
 */
class Ingo_Script_Imap_Mock extends Ingo_Script_Imap_Api
{
    /**
     * TODO
     */
    protected $_fixtures = array();

    /**
     * TODO
     */
    protected $_folders = array();

    /**
     * TODO
     */
    public function loadFixtures($dir)
    {
        $this->_fixtures = array();

        $dh = opendir($dir);
        while (($dent = readdir($dh)) !== false) {
            if (!in_array($dent, array('.', '..'))) {
                $this->_fixtures[$dent] = Horde_Mime_Headers::parseHeaders(file_get_contents($dir . '/' . $dent));
            }
        }
        closedir($dh);

        $i = 0;
        foreach (array_keys($this->_fixtures) as $key) {
            $this->_folders['INBOX'][] = array('uid'     => ++$i,
                                               'fixture' => $key,
                                               'deleted' => false);
        }
    }

    /**
     * TODO
     */
    public function hasMessage($fixture, $folder = 'INBOX')
    {
        if (empty($this->_folders[$folder])) {
            return false;
        }
        foreach ($this->_folders[$folder] as $message) {
            if ($message['fixture'] == $fixture) {
                return !$message['deleted'];
            }
        }
        return false;
    }

    /**
     * @todo The new Horde_Imap library *only* does server-side searches now,
     *       so we can't use Horde_Imap_Client_Search_Query for mock searches
     *       anymore.
     */
    public function search($query)
    {
        $result = array();
        foreach ($this->_folders['INBOX'] as $message) {
            if ($message['deleted']) {
                continue;
            }
            if ($query->matches($this->_fixtures[$message['fixture']])) {
                $result[] = $message['uid'];
            }
        }
        return $result;
    }

    /**
     * TODO
     */
    public function deleteMessages($indices)
    {
        foreach (array_keys($this->_folders['INBOX']) as $i) {
            if (in_array($this->_folders['INBOX'][$i]['uid'], $indices)) {
                unset($this->_folders['INBOX'][$i]);
            }
        }

        // Force renumbering
        $this->_folders['INBOX'] = array_merge($this->_folders['INBOX'], array());
    }

    /**
     * TODO
     */
    public function moveMessages($indices, $folder)
    {
        foreach (array_keys($this->_folders['INBOX']) as $i) {
            if (in_array($this->_folders['INBOX'][$i]['uid'], $indices)) {
                $this->_folders[$folder][] = $this->_folders['INBOX'][$i];
            }
        }
        return $this->deleteMessages($indices);
    }

    /**
     * TODO
     */
    public function fetchEnvelope($indices)
    {
        $result = array();

        foreach ($indices as $uid) {
            foreach (array_keys($this->_folders['INBOX']) as $i) {
                if ($this->_folders['INBOX'][$i]['uid'] == $uid) {
                    $fetch = new Horde_Imap_Client_Data_Fetch();
                    $fetch->setEnvelope(array(
                        'from' => $this->_fixtures[$this->_folders['INBOX'][$i]['fixture']]->getValue('from')
                    ));
                    $fetch->setUid = $uid;
                    $result[$uid] = $fetch;
                }
            }
        }

        return $result;
    }

}
