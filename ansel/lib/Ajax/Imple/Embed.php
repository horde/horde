<?php
/**
 * Ansel_Ajax_Imple_Embed:: Class for embedding a small gallery widget in external
 * websites. Meant to be called via a single script tag, therefore this will
 * always return nothing but valid javascript.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Ansel
 */
class Ansel_Ajax_Imple_Embed extends Horde_Core_Ajax_Imple
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
    public function handle($args, $post)
    {
        /* First, determine the type of view we are asking for */
        $view = empty($args['gallery_view']) ? 'Mini' : $args['gallery_view'];
        $class = 'Ansel_View_EmbeddedRenderer_' . basename($view);
        if (!class_exists($class)) {
            throw new Horde_Exception(sprintf("Class definition for %s not found.", $class));
        }

        try {
            $view = new $class($args);
            header('Content-Type: script/javascript');
            return $view->html();
        } catch (Exception $e) {}
    }

}
