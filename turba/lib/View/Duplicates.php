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
     * A field name.
     *
     * @var string
     */
    protected $_type;

    /**
     * A duplicate value.
     *
     * @var string
     */
    protected $_duplicate;

    /**
     * A Turba_Driver instance.
     *
     * @var Turba_Driver
     */
    protected $_driver;

    /**
     * Constructor.
     *
     * If the $type and $duplicate parameters are specified, they are used to
     * lookup a single Turba_List from $duplicates with a list of duplicate
     * contacts. The resolution interface for those duplicates is rendered
     * above the overview tables then.
     *
     * @param array $duplicates     Hash of Turba_List objects.
     * @param Turba_Driver $driver  A Turba_Driver instance.
     * @param string $type          A field name.
     * @param string $duplicate     A duplicate value.
     */
    public function __construct(array $duplicates, Turba_Driver $driver,
                                $type = null, $duplicate = null)
    {
        $this->_duplicates = $duplicates;
        $this->_driver     = $driver;
        $this->_type       = $type;
        $this->_duplicate  = $duplicate;
    }

    /**
     * Renders this view.
     */
    public function display()
    {
        $view = new Horde_View(array('templatePath' => TURBA_TEMPLATES . '/search/duplicate'));
        new Horde_View_Helper_Text($view);

        $hasDuplicate = $this->_type && $this->_duplicate &&
            isset($this->_duplicates[$this->_type]) &&
            isset($this->_duplicates[$this->_type][$this->_duplicate]);
        if ($hasDuplicate) {
            $vars = new Horde_Variables();
            $view->type = $GLOBALS['attributes'][$this->_type]['label'];
            $view->value = $this->_duplicate;
            echo $view->render('header');

            $view->contactUrl = Horde::applicationUrl('contact.php');
            $view->mergeUrl = Horde::applicationUrl('merge.php');
            $view->first = true;
            $duplicate = $this->_duplicates[$this->_type][$this->_duplicate];
            while ($contact = $duplicate->next()) {
                $contact->lastModification();
            }
            $duplicate->sort(array(array('field' => '__modified', 'ascending' => false)));
            $view->mergeTarget = $duplicate->reset()->getValue('__key');
            while ($contact = $duplicate->next()) {
                $view->source = $contact->getSource();
                $view->id = $contact->getValue('__key');
                $history = $contact->getHistory();
                if (isset($history['modified'])) {
                    $view->changed = $history['modified'];
                } elseif (isset($history['created'])) {
                    $view->changed = $history['created'];
                } else {
                    unset($view->changed);
                }
                echo $view->render('contact_header');
                $contactView = new Turba_Form_Contact($vars, $contact, false);
                $contactView->renderInactive(new Horde_Form_Renderer(), $vars);
                echo $view->render('contact_footer');
                $view->first = false;
            }

            echo $view->render('footer');
        }

        $view->duplicates = $this->_duplicates;
        $view->hasDuplicate = (bool)$hasDuplicate;
        $view->attributes = $GLOBALS['attributes'];
        $view->link = Horde::applicationUrl('search.php')
            ->add(array('source' => $this->_driver->name,
                        'search_mode' => 'duplicate'));

        echo $view->render('list');
    }
}
