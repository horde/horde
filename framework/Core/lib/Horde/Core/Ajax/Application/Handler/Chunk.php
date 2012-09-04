<?php
/**
 * Defines AJAX calls used to send raw content to the browser.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Application_Handler_Chunk extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Loads a chunk of PHP code (usually an HTML template) from the
     * application's templates directory.
     *
     * @return object  Object with the following properties:
     *   - chunk: (string) A chunk of PHP output.
     */
    public function chunkContent()
    {
        $chunk = basename($this->vars->chunk);

        $result = new stdClass;
        if (!empty($chunk)) {
            Horde::startBuffer();
            include $GLOBALS['registry']->get('templates', $this->_base->app) . '/chunks/' . $chunk . '.php';
            $result->chunk = Horde::endBuffer();
        }

        return $result;
    }

}
