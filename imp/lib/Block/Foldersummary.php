<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Horde_Block
 */

class IMP_Block_Foldersummary extends Horde_Block
{
    protected $_app = 'imp';

    protected function _content()
    {
        $imp_ui = new IMP_Ui_Block();
        list($html,) = $imp_ui->folderSummary('dimp');

        return '<table cellspacing="0" width="100%">' . $html . '</table>';
    }

}
