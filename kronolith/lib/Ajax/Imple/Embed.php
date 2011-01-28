<?php
/**
 * Kronolith_Ajax_Imple_Embed:: will allow embedding calendar widgets in
 * external websites. Meant to be called via a single script tag, therefore
 * this will always return nothing but valid javascript.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Ajax_Imple_Embed extends Horde_Core_Ajax_Imple
{
    /**
     */
    public function attach()
    {
    }

    public function getUrl()
    {
        return $this->_getUrl('Embed', 'kronolith', $this->_params, true);
    }

    /**
     * Handles the output of the embedded widget. This must always be valid
     * javascript.
     * <pre>
     * The following arguments are required:
     *   view      => the view (block) we want
     *   container => the DOM node to populate with the widget
     *   calendar  => the share_name for the requested calendar
     *
     * The following are optional (and are not used for all views)
     *   months        => the number of months to include
     *   maxevents     => the maximum number of events to show
     * </pre>
     *
     * @param array $args  Arguments for this view.
     */
    public function handle($args, $post)
    {
        /* First, determine the type of view we are asking for */
        $view = $args['view'];

        /* The DOM container to put the HTML in on the remote site */
        $container = $args['container'];

        /* The share_name of the calendar to display */
        $calendar = $args['calendar'];

        /* Deault to showing only 1 month when we have a choice */
        $count_month = (!empty($args['months']) ? $args['months'] : 1);

        /* Default to no limit for the number of events */
        $max_events = (!empty($args['maxevents']) ? $args['maxevents'] : 0);

        /* Default to one week */
        $count_days = (!empty($args['days']) ? $args['days'] : 7);

        if (!empty($args['css']) && $args['css'] == 'none') {
            $nocss = true;
        }

        /* Build the block parameters */
        $params = array(
            'calendar' => $calendar,
            'maxevents' => $max_events,
            'months' => $count_month,
            'days' => $count_days
        );

        /* Call the Horde_Block api to get the calendar HTML */
        $title = $GLOBALS['registry']->call('horde/blockTitle', array('kronolith', $view, $params));
        $results = $GLOBALS['registry']->call('horde/blockContent', array('kronolith', $view, $params));

        /* Some needed paths */
        $js_path = $GLOBALS['registry']->get('jsuri', 'kronolith');

        /* Local js */
        $jsurl = Horde::url($js_path . '/embed.js', true);

        /* Horde's js */
        $hjs_path = $GLOBALS['registry']->get('jsuri', 'horde');
        $hjsurl = Horde::url($hjs_path . '/tooltips.js', true);
        $pturl = Horde::url($hjs_path . '/prototype.js', true);

        /* CSS */
        if (empty($nocss)) {
            $horde_css = $GLOBALS['injector']->getInstance('Horde_Themes_Css');
            $horde_css->addThemeStylesheet('embed.css');

            Horde::startBuffer();
            Horde::includeStylesheetFiles(array('nobase' => true), true);
            $css = Horde::endBuffer();
        } else {
            $css = '';
        }

        /* Escape the text and put together the javascript to send back */
        $results = addslashes('<div class="kronolith_embedded"><div class="title">' . $title . '</div>' . $results . '</div>');
        $html = <<<EOT
        //<![CDATA[
        if (typeof kronolith == 'undefined') {
            if (typeof Prototype == 'undefined') {
                document.write('<script type="text/javascript" src="$pturl"></script>');
            }
            if (typeof Horde_ToolTips == 'undefined') {
                Horde_ToolTips_Autoload = false;
                document.write('<script type="text/javascript" src="$hjsurl"></script>');
            }
            kronolith = new Object();
            kronolithNodes = new Array();
            document.write('<script type="text/javascript" src="$jsurl"></script>');
            document.write('$css');
        }
        kronolithNodes[kronolithNodes.length] = '$container';
        kronolith['$container'] = "$results";
        //]]>
EOT;

        /* Send it */
        header('Content-Type: text/javascript');
        echo $html;
        exit;
    }

}
