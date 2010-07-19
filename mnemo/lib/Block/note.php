<?php

$block_name = _("View note");

/**
 * Implementation of Horde_Block api to show a single note.
 *
 * @package Horde_Block
 */
class Horde_Block_Mnemo_note extends Horde_Block
{
    protected $_app = 'mnemo';
    private $_notename = '';

    protected function _params()
    {
        global $prefs;
        $memos = Mnemo::listMemos($prefs->getValue('sortby'),
                                  $prefs->getValue('sortdir'));
        $notes = array();
        foreach ($memos as $memo) {
            $notes[$memo['uid']] = $memo['desc'];
        }

        return array(
            'note_uid' => array(
                'type' => 'enum',
                'name' => _("Show this note"),
                'values' => $notes,
            )
        );
    }

    protected function _title()
    {
        return htmlspecialchars($this->_getTitle());
    }

    protected function _content()
    {
        $memo = $this->_getNote();
        $html = '<div class="noteBody">';
        $body = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($memo['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        try {
            $body = Horde::callHook('format_description', array($body), 'mnemo', $body);
        } catch (Horde_Exception_HookNotSet $e) {}
        $html .= $body . '</div>';

        return $html;
    }

    private function _getNote()
    {
        if (!isset($this->_params['note_uid'])) {
            throw new Horde_Block_Exception(_("No note loaded"));
        }

        $uid = $this->_params['note_uid'];
        $storage = Mnemo_Driver::singleton();
        $memo = $storage->getByUID($uid);
        if (is_a($memo, 'PEAR_Error')) {
            if (!empty($this->_notename)) {
                $msg = sprintf(_("An error occurred displaying %s"), $this->_notename);
            } else {
                $msg = _("An error occurred displaying the note");
            }
            throw new Horde_Block_Exception($msg);
        }

        return $memo;
    }

    private function _getTitle()
    {
        if (empty($this->_notename)) {
            $note = $this->_getNote();
            $this->_notename = $note['desc'];
        }
        return $this->_notename;
    }
}
