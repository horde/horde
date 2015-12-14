<?php
/**
 * This is a view of Ansel's sidebar.
 *
 * This is for the dynamic view. For traditional the view, see
 * Ansel_Application::sidebar().
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_View_Sidebar extends Horde_View_Sidebar
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        global $prefs, $registry, $injector;

        parent::__construct($config);

        $blank = new Horde_Url();
        $this->addNewButton(
            _("Add Gallery"),
            $blank,
            array('id' => 'anselSideBarAddGallery')
        );

        $sidebar = $injector->createInstance('Horde_View');

        $tagger = $injector->getInstance('Ansel_Tagger');
        $sidebar->tags = $tagger->listImageTags();

        $this->content = $sidebar->render('dynamic/sidebar');
    }

}
