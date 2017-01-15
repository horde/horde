<?php
/**
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Oliver Kuhl <okuhl@netcologne.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Block to show filter information.
 *
 * @author   Oliver Kuhl <okuhl@netcologne.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
        global $injector, $session;

        /* Get list of filters */
        $filters = Ingo_Storage_FilterIterator_Match::create(
            $injector->getInstance('Ingo_Factory_Storage')->create(),
            $session->get('ingo', 'script_categories')
        );

        $html = '<table class="ingoBlockSummary">';

        foreach ($filters as $rule) {
            $active = $rule->disable
                ? _("inactive")
                : _("active");

            switch (get_class($rule)) {
            case 'Ingo_Rule_System_Vacation':
                $html .= '<tr><td>' .
                    '<span class="iconImg vacationImg"></span>' .
                    '</td><td>' .
                    Ingo_Basic_Vacation::url()->link(array('title' => _("Edit"))) .
                    _("Vacation") . '</a> ' . $active .
                    '</td></tr>';
                break;

            case 'Ingo_Rule_System_Forward':
                $html .= '<tr><td>' .
                    '<span class="iconImg forwardImg"></span>' .
                    '</td><td>' .
                    Ingo_Basic_Forward::url()->link(array('title' => _("Edit"))) .
                    _("Forward") . '</a> ' . $active;
                $addr = $rule->addresses;
                if (!empty($addr)) {
                    $html .= ':<br />' . implode('<br />', $addr);
                }
                $html .= '</td></tr>';
                break;

            case 'Ingo_Rule_System_Whitelist':
                $html .= '<tr><td>' .
                    '<span class="iconImg whitelistImg"></span>' .
                    '</td><td>' .
                    Ingo_Basic_Whitelist::url()->link(array('title' => _("Edit"))) .
                    _("Whitelist") . '</a> ' . $active .
                   '</td></tr>';
                break;

            case 'Ingo_Rule_System_Blacklist':
                $html .= '<tr><td>' .
                    '<span class="iconImg blacklistImg"></span>' .
                    '</td><td>' .
                    Ingo_Basic_Blacklist::url()->link(array('title' => _("Edit"))) .
                    _("Blacklist") . '</a> ' . $active .
                    '</td></tr>';
                break;

            case 'Ingo_Rule_Spam Filter':
                $html .= '<tr><td>' .
                    '<span class="iconImg spamImg"></span>' .
                    '</td><td>' .
                    Ingo_Basic_Spam::url()->link(array('title' => _("Edit"))) .
                    _("Spam Filter") . '</a> ' . $active .
                    '</td></tr>';
                break;
            }
        }

        return $html . '</table>';
    }

}
