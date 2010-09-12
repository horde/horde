<?php
/**
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */
class Chora_Renderer_File_Pretty extends Chora_Renderer_File
{
    public function render()
    {
        // need the checkout
        $checkOut = $GLOBALS['VC']->checkout($this->_file->queryPath(), $this->_revision);

        // Pretty-print the checked out copy */
        if ($this->_file->mimeType == 'application/octet-stream') {
            $this->_view->mimeType = 'text/plain';
        } else {
            $this->_view->mimeType = $this->_file->mimeType;
        }

        $this->_view->title = $this->_file->queryName();
        $this->_view->pretty = Chora::pretty($this->_view->mimeType, $checkOut);
        if ($this->_view->mimeType == 'text/html') {
            $this->_view->pretty->setConfigParam('inline', true);
        }

        return $this->_view->render('app/views/file/pretty.html.php');
    }
}
