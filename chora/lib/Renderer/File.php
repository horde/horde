<?php
/**
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */
class Chora_Renderer_File
{
    /**
     * @var Horde_View
     */
    protected $_view;

    /**
     * @var Horde_Vcs_File
     */
    protected $_file;

    /**
     * @var string
     */
    protected $_revision;

    public function __construct(Horde_View $view, Horde_Vcs_File $file, $revision)
    {
        $this->_view = $view;
        $this->_file = $file;
        $this->_revision = $revision;
    }
}
