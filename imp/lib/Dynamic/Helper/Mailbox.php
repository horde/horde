<?php
/**
 * View helper class for the dynamic mailbox page.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
 */
class IMP_Dynamic_Helper_Mailbox extends Horde_View_Helper_Base
{
    /**
     * Outputs a sidebar link.
     *
     * @param string $id     DOM ID.
     * @param string $text   Button text.
     * @param string $image  CSS class of image to use.
     */
    public function sidebarLink($id, $text, $image)
    {
        $ak = Horde::getAccessKey($text, true);
        return '<li class="servicelink" id="' . $id . '"' .
            (strlen($ak) ? ' accesskey="' . $ak . '"' : '') . '>' .
            '<span class="iconImgSidebar ' . $image . '"></span>' .
            '<a>' . Horde::highlightAccessKey($text, $ak) . '</a></li>';
    }

    /**
     * Outputs the portal link.
     */
    public function portalLink()
    {
        $ak = Horde::getAccessKey(_("_Portal"), true);
        return '<a href="' .
            $GLOBALS['registry']->getServiceLink('portal') . '"' .
            (strlen($ak) ? ' accesskey="' . $ak . '"' : '') . '>' .
            Horde::highlightAccessKey(_("_Portal"), $ak) . '</a>';
    }

    /**
     * Return a Horde image object.
     *
     * @param string $img  Image name.
     * @param array $opts  Additional options to pass to Horde::img().
     *
     * @return Horde_Themes_Image  Image object.
     */
    public function hordeImg($img, array $opts = array())
    {
        return Horde::img($img, '', $opts);
    }

}
