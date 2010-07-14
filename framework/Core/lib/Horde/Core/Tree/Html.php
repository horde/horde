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
     *
     * @var array
     */
    protected $_images = array(
        'line' => 'line.png',
        'blank' => 'blank.png',
        'join' => 'join.png',
        'join_bottom' => 'joinbottom.png',
        'plus' => 'plus.png',
        'plus_bottom' => 'plusbottom.png',
        'plus_only' => 'plusonly.png',
        'minus' => 'minus.png',
        'minus_bottom' => 'minusbottom.png',
        'minus_only' => 'minusonly.png',
        'null_only' => 'nullonly.png',
        'folder' => 'folder.png',
        'folderopen' => 'folderopen.png',
        'leaf' => 'leaf.png'
    );

    /**
     * Constructor.
     *
     * @param string $name   @see parent::__construct().
     * @param array $params  @see parent::__construct().
     */
    public function __construct($name, array $params = array())
    {
        parent::__construct($name, $params);

        if (!empty($GLOBALS['registry']->nlsconfig['rtl'][$GLOBALS['language']])) {
            $no_rev = array('blank', 'folder', 'folder_open');
            foreach (array_diff(array_keys($this->_images), $no_rev) as $key) {
                $this->_images[$key] = 'rev-' . $this->_images[$key];
            }
        }

        foreach (array_keys($this->_images) as $key) {
            $this->_images[$key] = strval(Horde_Themes::img('tree/' . $this->_images[$key], array('app' => 'horde')));
        }
    }

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

}
