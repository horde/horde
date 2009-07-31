<?php
if (!empty($GLOBALS['conf']['faces']['driver'])) {
    $block_name = _("Recent faces");
}

/**
 * This file provides a recent faces through the Horde_Blocks, by extending
 * the Horde_Blocks class.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <Duck@obala.net>
 * @package Horde_Block
 */
class Horde_Block_ansel_recent_faces extends Horde_Block
{
    var $_app = 'ansel';

    function _params()
    {
        return array('limit' => array(
                        'name' => _("Maximum number of faces"),
                        'type' => 'int',
                        'default' => 10));
    }

    function _title()
    {
        return _("Recent faces");
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        $faces = Ansel_Faces::factory();
        $results = $faces->allFaces(0, $this->_params['limit']);
        $html = '';
        foreach ($results as $face_id => $face) {
            $facename = htmlspecialchars($face['face_name'], ENT_COMPAT, Horde_Nls::getCharset());
            $html .= '<a href="' . Ansel_Faces::getLink($face) . '" title="' . $facename . '">'
                    . '<img src="' . $faces->getFaceUrl($face['image_id'], $face_id)
                    . '" style="padding-bottom: 5px; padding-left: 5px" alt="' . $facenane  . '" /></a>';
        }

        return $html;
    }

}
