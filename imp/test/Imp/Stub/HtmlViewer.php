<?php
/**
 * Stub for testing the IMP HTML Mime Viewer driver.
 * Needed because we need to overwrite a protected method.
 *
 * PHP version 5
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Stub for testing the IMP HTML Mime Viewer driver.
 * Needed because we need to overwrite a protected method.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Stub_Mime_Viewer_Html extends IMP_Mime_Viewer_Html
{
    public function runTest($html)
    {
        $this->_imptmp = array(
            'blockimg' => 'imgblock.png',
            'img' => true,
            'imgblock' => false,
            'inline' => true,
            'target' => '_blank'
        );

        $dom = new Horde_Domhtml($html);
        $this->_node($dom->dom, $dom->dom);

        return $dom->dom->saveXML($dom->dom->getElementsByTagName('body')->item(0)->firstChild);
    }

    protected function _node($doc, $node)
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->_nodeCallback($doc, $child);
                $this->_node($doc, $child);
            }
        }
    }

}
