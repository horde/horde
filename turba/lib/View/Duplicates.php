<?php
/**
 * The Turba_View_Duplicates class provides an interface for displaying and
 * resolving duplicate contacts.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Turba
 */
class Turba_View_Duplicates
{
    /**
     * Hash of Turba_List objects.
     *
     * @var array
     */
    protected $_duplicates;

    /**
     * A Turba_Driver instance.
     *
     * @var Turba_Driver
     */
    protected $_driver;

    /**
     * Constructor.
     *
     * @param array $duplicates     Hash of Turba_List objects.
     * @param Turba_Driver $driver  A Turba_Driver instance.
     */
    public function __construct(array $duplicates, Turba_Driver $driver)
    {
        $this->_duplicates = $duplicates;
        $this->_driver     = $driver;
    }

    public function display()
    {
        require TURBA_BASE . '/config/attributes.php';
        $view = new Horde_View(array('templatePath' => TURBA_TEMPLATES . '/search'));
        new Horde_View_Helper_Text($view);
        $view->duplicates = $this->_duplicates;
        $view->attributes = $attributes;
        $view->link = Horde::applicationUrl('search.php')
            ->add(array('source' => $this->_driver->name,
                        'search_mode' => 'duplicate',
                        'search' => 1));

        echo $view->render('duplicate_list');
    }
}
