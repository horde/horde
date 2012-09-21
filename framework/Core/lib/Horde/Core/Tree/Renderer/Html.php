<?php
/**
 * The Horde_Core_Tree_Renderer_Html class extends the Horde_Tree_Html
 * class to provide for creation of Horde-specific URLs.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Tree_Renderer_Html extends Horde_Tree_Renderer_Html
{
    /**
     * Images array.
     * Values correspond to 'horde-tree-image-#' CSS classes in horde/themes/screen.css.
     *
     * @var array
     */
    protected $_images = array(
        'line' => 1,
        'blank' => '',
        'join' => 2,
        'join_bottom' => 4,
        'join_top' => 3,
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
        return Horde::selfUrl()
            ->add(Horde_Tree::TOGGLE . $this->_tree->instance, $node_id)
            ->link();
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
        case 'horde-tree-icon':
            return parent::_generateImage($src, $class, $alt);

        case 'horde-tree-toggle':
            $class .= ' horde-tree-image';
            break;

        default:
            $class = 'horde-tree-image';
            break;
        }

        $img = '<span class="' . $class . ' horde-tree-image-' . $src . '"';

        if (!is_null($alt)) {
            $img.= ' alt="' . $alt . '"';
        }

        return $img . '></span>';
    }

}
