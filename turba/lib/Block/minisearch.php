<?php

$block_name = _("Contact Search");

/**
 * This is an implementation of the Horde_Block API that allows searching of
 * address books from the portal.
 *
 * @package Horde_Block
 */
class Horde_Block_turba_minisearch extends Horde_Block {

    var $_app = 'turba';

    /**
     * The title to go in this block.
     *
     * @return string  The title text.
     */
    function _title()
    {
        return Horde::link(Horde::url($GLOBALS['registry']->getInitialPage(),
                                      true))
            . _("Contact Search") . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string  The block content.
     */
    function _content()
    {
        if ($GLOBALS['browser']->hasFeature('iframes')) {
            Horde::addScriptFile('prototype.js', 'horde');
            return Horde_Util::bufferOutput(
                'include',
                TURBA_TEMPLATES . '/block/minisearch.inc');
        } else {
            return '<em>' . _("A browser that supports iframes is required")
                . '</em>';
        }
    }

}
