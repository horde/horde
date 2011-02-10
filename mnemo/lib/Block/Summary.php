<?php
/**
 */
class Mnemo_Block_Summary extends Horde_Block
{
    /**
     */
    protected $_app = 'mnemo';

    /**
     */
    public function getName()
    {
        return _("Notes Summary");
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
        $cManager = new Horde_Prefs_CategoryManager();
        $categories = array();
        foreach ($cManager->get() as $c) {
            $categories[$c] = $c;
        }

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
            'show_categories' => array(
                'type' => 'multienum',
                'name' => _("Show notes from these categories"),
                'default' => array(),
                'values' => $categories
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $registry, $prefs;

        $cManager = new Horde_Prefs_CategoryManager();
        $colors = $cManager->colors();
        $fgcolors = $cManager->fgColors();

        if (!empty($this->_params['show_notepad'])) {
            $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
        }

        $html = '';
        $memos = Mnemo::listMemos($prefs->getValue('sortby'),
                                  $prefs->getValue('sortdir'));
        foreach ($memos as $id => $memo) {
            if (!empty($this->_params['show_categories']) &&
                !in_array($memo['category'], $this->_params['show_categories'])) {
                continue;
            }

            $html .= '<tr>';

            if (!empty($this->_params['show_actions'])) {
                $editImg = Horde_Themes::img('edit.png');
                $editurl = Horde_Util::addParameter(
                    'memo.php',
                    array('memo' => $memo['memo_id'],
                          'memolist' => $memo['memolist_id']));
                $html .= '<td width="1%">'
                    . Horde::link(htmlspecialchars(Horde::url(Horde_Util::addParameter($editurl, 'actionID', 'modify_memo'), true)), _("Edit Note"))
                    . Horde::img($editImg, _("Edit Note"))
                    . '</a></td>';
            }

            if (!empty($this->_params['show_notepad'])) {
                $owner = $memo['memolist_id'];
                $share = $shares->getShare($owner);
                $owner = $share->get('name');
                $html .= '<td>' . htmlspecialchars($owner) . '</td>';
            }

            $viewurl = Horde_Util::addParameter(
                'view.php',
                array('memo' => $memo['memo_id'],
                      'memolist' => $memo['memolist_id']));

            $html .= '<td>'
                . Horde::linkTooltip(
                    htmlspecialchars(Horde::url($viewurl, true)),
                    '', '', '', '',
                    $memo['body'] != $memo['desc'] ? Mnemo::getNotePreview($memo) : '')
                . (strlen($memo['desc']) ? htmlspecialchars($memo['desc']) : '<em>' . _("Empty Note") . '</em>')
                . '</a></td><td width="1%" class="category'
                . md5($memo['category']) . '">'
                . htmlspecialchars($memo['category'] ? $memo['category'] : _("Unfiled"))
                . "</td></tr>\n";
        }

        if (!$memos) {
            return '<p><em>' . _("No notes to display") . '</em></p>';
        }

        $GLOBALS['injector']->getInstance('Horde_Themes_Css')->addThemeStylesheet('categoryCSS.php');

        return '<table cellspacing="0" width="100%" class="linedRow">' . $html
            . '</table>';
    }

}
