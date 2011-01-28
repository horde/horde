<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Form_ImageDate extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars, $title)
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
