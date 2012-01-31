<?php
/**
 * Block to show filter information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Oliver Kuhl <okuhl@netcologne.de>
 */
class Ingo_Block_Overview extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Overview");
    }

    /**
     */
    protected function _title()
    {
        return Horde::url($GLOBALS['registry']->getInitialPage(), true)->link() . $GLOBALS['registry']->get('name') . '</a>';
    }

    /**
     */
    protected function _content()
    {
        /* Get list of filters */
        $filters = $GLOBALS['injector']->getInstance('Ingo_Factory_Storage')->create()->retrieve(Ingo_Storage::ACTION_FILTERS);
        $html = '<table class="ingoBlockSummary">';

        foreach ($filters->getFilterList() as $filter) {
            if (!empty($filter['disable'])) {
                $active = _("inactive");
            } else {
                $active = _("active");
            }

            $s_categories = $GLOBALS['session']->get('ingo', 'script_categories');

            switch ($filter['name']) {
            case 'Vacation':
                if (in_array(Ingo_Storage::ACTION_VACATION, $s_categories)) {
                    $html .= '<tr><td>' .
                        '<span class="iconImg vacationImg"></span>' .
                        '</td><td>' .
                        Horde::url('vacation.php')->link(array('title' => _("Edit"))) .
                        _("Vacation") . '</a> ' . $active .
                        '</td></tr>';
                }
                break;

            case 'Forward':
                if (in_array(Ingo_Storage::ACTION_FORWARD, $s_categories)) {
                    $html .= '<tr><td>' .
                        '<span class="iconImg forwardImg"></span>' .
                        '</td><td>' .
                        Horde::url('forward.php')->link(array('title' => _("Edit"))) .
                        _("Forward") . '</a> ' . $active;
                    $data = unserialize($GLOBALS['prefs']->getValue('forward'));
                    if (!empty($data['a'])) {
                        $html .= ':<br />' . implode('<br />', $data['a']);
                    }
                    $html .= '</td></tr>';
                }
                break;

            case 'Whitelist':
                if (in_array(Ingo_Storage::ACTION_WHITELIST, $s_categories)) {
                    $html .= '<tr><td>' .
                        '<span class="iconImg whitelistImg"></span>' .
                        '</td><td>' .
                        Horde::url('whitelist.php')->link(array('title' => _("Edit"))) .
                        _("Whitelist") . '</a> ' . $active .
                       '</td></tr>';
                }
                break;

            case 'Blacklist':
                if (in_array(Ingo_Storage::ACTION_BLACKLIST, $s_categories)) {
                    $html .= '<tr><td>' .
                        '<span class="iconImg blacklistImg"></span>' .
                        '</td><td>' .
                        Horde::url('blacklist.php')->link(array('title' => _("Edit"))) .
                        _("Blacklist") . '</a> ' . $active .
                        '</td></tr>';
                }
                break;

            case 'Spam Filter':
                if (in_array(Ingo_Storage::ACTION_SPAM, $s_categories)) {
                    $html .= '<tr><td>' .
                        '<span class="iconImg spamImg"></span>' .
                        '</td><td>' .
                        Horde::url('spam.php')->link(array('title' => _("Edit"))) .
                        _("Spam Filter") . '</a> ' . $active .
                        '</td></tr>';
                }
                break;
            }

        }

        return $html . '</table>';
    }

}
