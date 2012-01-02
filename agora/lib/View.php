<?php
/**
 * Agora General View Class
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Duck <duck@obala.net>
 * @package Agora
 */
class Agora_View extends Horde_View {

    /**
     * Constructor
     */
    public function __construct()
    {
        /* Set default data. */
        parent::__construct(array('templatePath' => AGORA_TEMPLATES));
    }

}
