<?php
/**
 * Ansel_Ajax_Imple_Embed:: Class for embedding a small gallery widget in external
 * websites. Meant to be called via a single script tag, therefore this will
 * always return nothing but valid javascript.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Ajax_Imple_Embed extends Horde_Ajax_Imple_Base
{
    // Noop since we don't attach this to any UI element.
    public function attach(){}

    public function getUrl()
    {
        return $this->_getUrl('Embed', 'ansel', $this->_params, true);
    }

    /**
     * Handles the output of the embedded widget. This must always be valid
     * javascript.
     *
     * @see Ansel_View_Embedded for parameters.
     *
     * @param array $args  Arguments for this view.
     */
    public function handle($args)
    {
        include_once dirname(__FILE__) . '/../../base.php';

        /* First, determine the type of view we are asking for */
        $view = empty($args['gallery_view']) ? 'Mini' : $args['gallery_view'];

        require_once ANSEL_BASE . '/lib/Views/EmbeddedRenderers/' . basename($view) . '.php';
        $class = 'Ansel_View_EmbeddedRenderer_' . basename($view);
        if (!class_exists($class)) {
            return '';
        }

        $view = call_user_func(array($class, 'makeView'), $args);

        header('Content-Type: script/javascript');
        return $view->html();
    }

}
