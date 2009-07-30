<?php
/**
 * $Horde: ansel/lib/Forms/ImageDate.php,v 1.3 2009/01/06 17:48:53 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

/** Horde_Form **/
require_once 'Horde/Form.php';

class ImageDateForm extends Horde_Form {

    var $_useFormToken = false;

    function ImageDateForm(&$vars, $title)
    {
        global $gallery;

        parent::Horde_Form($vars, $title);

        $this->setButtons(_("Save"));
        $this->addHidden('', 'actionID', 'text', false);
        $this->addHidden('', 'gallery', 'text', false);
        $this->addHidden('', 'image', 'text', false);
        $this->addHidden('', 'page', 'text', false);
        $this->addVariable(_("Editing dates for the following photos"), 'image_list', 'html', false, true);
        $this->addVariable(_("Original Date"), 'image_originalDate',
                           'monthdayyear', true, false, null,
                           array('start_year' => 1900));
    }

}