<?php
/**
 * Jeta_Applet_jta:: provides a driver for the JTA Java Applet.
 *
 * JTA applet (v2.6) located at:
 *    http://javassh.org/
 * JTA is released under the GPL license for non-commercial customers.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL).  If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Jeta
 */
class Jeta_Applet_jta extends Jeta_Applet
{
    /**
     * Jeta configuration parameters.
     *
     * @var array
     */
    protected $_jtaParams = array(
        'Socket.host' => array(
            'pref' => 'host',
            'bool' => false
        ),
        'Socket.port' => array(
            'pref' => 'port',
            'bool' => false,
        ),
        'Applet.detach' => array(
            'pref' => 'jta_detach',
            'bool' => true
        ),
        'Applet.detach.fullscreen' => array(
            'pref' => 'jta_detach_fullscreen',
            'bool' => true
        ),
        'Applet.detach.title' => array(
            'pref' => 'jta_detach_title',
            'bool' => false
        ),
        'Applet.detach.immediately' => array(
            'pref' => 'jta_detach_immediately',
            'bool' => true
        ),
        'Applet.detach.startText' => array(
            'pref' => 'jta_detach_start',
            'bool' => false
        ),
        'Applet.detach.stopText' => array(
            'pref' => 'jta_detach_stop',
            'bool' => false
        ),
        'Applet.detach.menuBar' => array(
            'pref' => 'jta_detach_menubar',
            'bool' => true
        ),
        'Applet.disconnect.closeWindow' => array(
            'pref' => 'jta_detach_disconnect',
            'bool' => true
        ),
        'Applet.disconnect' => array(
            'pref' => 'jta_disconnect',
            'bool' => true
        )
    );

    /**
     * Generate the HTML code used to load the applet.
     *
     * @return string  The HTML needed to load the applet.
     */
    public function generateAppletCode()
    {
        $params = array(
            'config' => 'jta.conf',
            'plugins' => 'Status,Socket,SSH,Terminal',
            'SSH.user' => (empty($GLOBALS['conf']['user']['hordeauth']) || ($GLOBALS['conf']['user']['hordeauth'] === 'full')) ? Horde_Auth::getAuth() : Horde_Auth::getBareAuth()
        );

        foreach ($this->_jtaParams as $key => $val) {
            $prefval = $GLOBALS['prefs']->getValue($val['pref']);
            $params[$key] = ($val['bool']) ? (($prefval) ? 'true' : 'false') : $prefval;
        }

        if (!empty($params['Applet.detach']) &&
            empty($params['Applet.detach.immediately'])) {
            $height = 75;
            $width = 100;
        } else {
            $height = 500;
            $width = 600;
        }

        return '<applet code="de.mud.jta.Applet" width="' . $width . '" height="' . $height . '" codebase="jar" archive="jta26.jar">' .
            $this->_generateParamTags($params) . '</applet>';
    }

}
