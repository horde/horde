<?php
/**
 * The Horde_Core_Tree_Html:: class extends the Horde_Tree_Html class to
 * provide for creation of Horde-specific URLs.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Tree_Html extends Horde_Tree_Html
{
    /**
     * Images array.
     * Values correspond to 'treeImg#' CSS classes in horde/themes/screen.css.
     *
     * @var array
     */
    protected $_images = array(
        'line' => 1,
        'blank' => '',
        'join' => 2,
        'join_bottom' => 4,
        'plus' => 10,
        'plus_bottom' => 11,
        'plus_only' => 12,
        'minus' => 6,
        'minus_bottom' => 7,
        'minus_only' => 8,
        'null_only' => 13,
        'folder' => 14,
        'folderopen' => 15,
        'leaf' => 16
    );

    /**
     * Generate a link URL tag.
     *
     * @param string $node_id  The node ID.
     *
     * @return string  The link tag.
     */
    protected function _generateUrlTag($node_id)
    {
        return Horde::link(Horde::selfUrl()->add(self::TOGGLE . $this->_instance, $node_id));
    }

    /**
     * Generate the icon image.
     *
     * @param string $src    The source image.
     * @param string $class  Additional class to add to image.
     * @param string $alt    Alt text to add to the image.
     *
     * @return string  A HTML tag to display the image.
     */
    protected function _generateImage($src, $class = '', $alt = null)
    {
        switch ($class) {
        case 'treeIcon':
            return parent::_generateImage($src, $class, $alt);

        case 'treeToggle':
            $class .= ' treeImg';
            break;

        default:
            $class = 'treeImg';
            break;
        }

        $img = '<span class="' . $class . ' treeImg' . $src . '"';

        if (!is_null($alt)) {
            $img.= ' alt="' . $alt . '"';
        }

        return $img . '></span>';
    }

}
