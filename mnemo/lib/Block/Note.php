<?php
/**
 */
class Mnemo_Block_Note extends Horde_Block
{
    /**
     */
    private $_notename = '';

    /**
     */
    public function getName()
    {
        return _("View note");
    }

    /**
     */
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

    /**
     */
    protected function _title()
    {
        return htmlspecialchars($this->_getTitle());
    }

    /**
     */
    protected function _content()
    {
        $memo = $this->_getNote();
        $html = '<div id="noteBody' . $memo['memo_id'] . '" class="noteBody">';
        $body = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($memo['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        try {
            $body = Horde::callHook('format_description', array($body), 'mnemo', $body);
        } catch (Horde_Exception_HookNotSet $e) {}
        $html .= $body . '</div>';
        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('mnemo', 'EditNote'), array(
            'domid' => 'noteBody' . $memo['memo_id'],
            'id' => $this->_params['note_uid'],
            'rows' => substr_count($memo['body'], "\n"),
        ));
        return $html;
    }

    /**
     */
    private function _getNote()
    {
        if (!isset($this->_params['note_uid'])) {
            throw new Horde_Block_Exception(_("No note loaded"));
        }

        $uid = $this->_params['note_uid'];
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        try {
            $memo = $storage->getByUID($uid);
        } catch (Mnemo_Exception $e) {
            if (!empty($this->_notename)) {
                $msg = sprintf(_("An error occurred displaying %s"), $this->_notename);
            } else {
                $msg = _("An error occurred displaying the note");
            }
            throw new Horde_Block_Exception($msg);
        }

        return $memo;
    }

    /**
     */
    private function _getTitle()
    {
        if (empty($this->_notename)) {
            $note = $this->_getNote();
            $this->_notename = $note['desc'];
        }
        return $this->_notename;
    }
}
