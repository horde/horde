<?php
/**
 * This class extends the base LoginTasks class in order to ensure Horde
 * tasks are always run first.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_LoginTasks extends Horde_LoginTasks
{
    /**
     * Horde application to run login tasks for.
     *
     * @var string
     */
    protected $_app;

    /**
     * @param string $app  Horde application string.
     */
    public function __construct(Horde_LoginTasks_Backend $backend, $app)
    {
        $this->_app = $app;

        parent::__construct($backend);
    }

    /**
     */
    public function runTasks(array $opts = array())
    {
        if (!isset($opts['url'])) {
            $opts['url'] = Horde::selfUrl(true, true, true);
        }

        if (($this->_app != 'horde') &&
            ($GLOBALS['session']->get('horde', 'logintasks/horde') !== true)) {
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_LoginTasks')->create('horde')->runTasks($opts);
        }

        parent::runTasks($opts);
    }

}
