<?php
/**
 * View helper class for smartmobile pages.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Smartmobile_View_Helper extends Horde_View_Helper_Base
{
    /**
     * Output the title bar.
     *
     * @param array $params  A list of parameters:
     *   - backlink: (mixed) Show backlink. If an array, first is URL to link
     *               to, second is label. If true, shows a basic Back link.
     *   - logout: (boolean) If true, show logout link.
     *   - portal: (boolean) If true, show portal link.
     *   - taptoggle: (boolean) Enable tap-toggle?
     *   - title: (string) If given, used as the title.
     *
     * @return string  Generated HTML code.
     */
    public function smartmobileHeader(array $params = array())
    {
        global $registry;

        $out = '<div data-position="fixed" data-role="header" data-tap-toggle="' .
            (empty($params['taptoggle']) ? 'false' : 'true') .
            '">';

        if (!empty($params['backlink'])) {
            if (is_array($params['backlink'])) {
                $out .= '<a class="smartmobile-back ui-btn-left" href="' .
                    $params['backlink'][0] .
                    '" data-icon="arrow-l" data-direction="reverse">' .
                    $params['backlink'][1] . '</a>';
            } else {
                $out .= '<a class="smartmobile-back ui-btn-left" href="#" ' .
                    'data-icon="arrow-l" data-rel="back">' . _("Back") . '</a>';
            }
        }

        if (!empty($params['portal']) &&
            ($portal = $registry->getServiceLink('portal', 'horde')->setRaw(false))) {
            $out .= '<a class="smartmobile-portal ui-btn-left" ' .
                'data-ajax="false" href="' . $portal . '">' .
                _("Applications") . '</a>';
        }

        if (isset($params['title']) && strlen($params['title'])) {
            $out .= '<h1 class="smartmobile-title">' . $params['title'] . '</h1>';
        }

        if (!empty($params['logout']) &&
            ($logout = $registry->getServiceLink('logout')->setRaw(false))) {
            $out .= '<a class="smartmobile-logout ui-btn-right" href="' .
                $logout .
                '" data-ajax="false" data-theme="e" data-icon="delete" class="ui-btn-right">' .
                _("Log out") . '</a>';
        }

        return $out . '</div>';
    }

}
