<?php
/**
 */
class Mnemo_Block_Summary extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Notes Summary");
    }

    /**
     */
    protected function _title()
    {
        global $registry;

        $label = !empty($this->_params['block_title'])
            ? $this->_params['block_title']
            : $registry->get('name');

        return Horde::link(Horde::url($registry->getInitialPage(), true))
            . htmlspecialchars($label) . '</a>';
    }

    /**
     */
    protected function _params()
    {
        return array(
            'show_actions' => array(
                'type' => 'checkbox',
                'name' => _("Show action buttons?"),
                'default' => 1
            ),
            'show_notepad' => array(
                'type' => 'checkbox',
                'name' => _("Show notepad name?"),
                'default' => 1
            ),
        );
    }

    /**
     */
    protected function _content()
    {
        global $registry, $prefs;

        if (!empty($this->_params['show_notepad'])) {
            $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
        }

        $html = '';
        $memos = Mnemo::listMemos($prefs->getValue('sortby'),
                                  $prefs->getValue('sortdir'));
        foreach ($memos as $id => $memo) {
            $html .= '<tr>';

            if (!empty($this->_params['show_actions'])) {
                $editImg = Horde_Themes::img('edit.png');
                $editurl = Horde::url('memo.php')->add(array('memo' => $memo['memo_id'], 'memolist' => $memo['memolist_id']));
                $html .= '<td width="1%">'
                    . Horde::link(htmlspecialchars(Horde::url($editurl, true)->add('actionID', 'modify_memo')), _("Edit Note"))
                    . Horde::img($editImg, _("Edit Note"))
                    . '</a></td>';
            }

            if (!empty($this->_params['show_notepad'])) {
                $html .= '<td>' . htmlspecialchars(Mnemo::getLabel($shares->getShare($memo['memolist_id']))) . '</td>';
            }

            $viewurl = Horde::url('view.php')->add(
                array('memo' => $memo['memo_id'],
                      'memolist' => $memo['memolist_id']));

            $html .= '<td>'
                . Horde::linkTooltip(
                    htmlspecialchars(Horde::url($viewurl, true)),
                    '', '', '', '',
                    $memo['body'] != $memo['desc'] ? Mnemo::getNotePreview($memo) : '')
                . (strlen($memo['desc']) ? htmlspecialchars($memo['desc']) : '<em>' . _("Empty Note") . '</em>')
                . '</a> <ul class="horde-tags">';
            foreach ($memo['tags'] as $tag) {
                $html .= '<li>' . htmlspecialchars($tag) . '</li>';
            }
            $html .= '</ul></td></tr>';
        }

        if (!$memos) {
            return '<p><em>' . _("No notes to display") . '</em></p>';
        }

        return '<table cellspacing="0" width="100%" class="linedRow">' . $html
            . '</table>';
    }

}
