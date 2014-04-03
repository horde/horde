<?php
/**
 * The factory for the calendars handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kronolith
 */
class Trean_Factory_TagBrowser
{
    /*
     * The injector.
     *
     * @var Horde_Injector
     */
    protected  $_injector;

    /**
     * The tag browser
     *
     * @var Trean_TagBrowser
     */
    protected $_browser;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return a Trean_TagBrowser
     *
     * @return Trean_TagBrowser
     */
    public function create()
    {
        if (empty($_browser)) {
            $this->_browser = new Trean_TagBrowser($this->_injector->getInstance('Trean_Tagger'));
        }

        return $this->_browser;
    }
}
