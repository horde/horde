<?php
/**
 * Jeta_Applet_sshtools:: provides a driver for the SSHTools Java Applet.
 *
 * SSHTools applet (v0.2.2) located at:
 *    http://sourceforge.net/projects/sshtools/
 * SSHTools is released under the GPL license.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL).  If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Jeta
 */
class Jeta_Applet_sshtools extends Jeta_Applet
{
    /**
     * SSHTools parameters.
     *
     * @var array
     */
    protected $_sshtoolsParams = array(
        'sshapps.connection.host' => array(
            'pref' => 'host',
            'bool' => false
        ),
        'sshapps.connection.port' => array(
            'pref' => 'port',
            'bool' => false
        ),
        'sshapps.connection.authenticationMethod' => array(
            'pref' => 'sshtools_auth',
            'bool' => false
        ),
        'sshapps.connection.connectImmediately' => array(
            'pref' => 'sshtools_connect_immediately',
            'bool' => true
        ),
        'sshapps.connection.showConnectionDialog' => array(
            'pref' => 'sshtools_connect_dialog',
            'bool' => true
        ),
        'sshapps.connection.disableHostKeyVerification' => array(
            'pref' => 'sshtools_disable_hostkey_verify',
            'bool' => true
        ),
        'sshapps.ui.toolBar' => array(
            'pref' => 'sshtools_show_toolbar',
            'bool' => true
        ),
        'sshapps.ui.menuBar' => array(
            'pref' => 'sshtools_show_menubar',
            'bool' => true
        ),
        'sshapps.ui.statusBar' => array(
            'pref' => 'sshtools_show_statusbar',
            'bool' => true
        ),
        'sshapps.ui.scrollBar' => array(
            'pref' => 'sshtools_show_scrollBar',
            'bool' => true
        ),
        'sshapps.ui.autoHide' => array(
            'pref' => 'sshtools_autohide',
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
            'sshapps.connection.userName' => $GLOBALS['registry']->getAuth((empty($GLOBALS['conf']['user']['hordeauth']) || ($GLOBALS['conf']['user']['hordeauth'] === 'full')) ? null : 'bare')
        );

        foreach ($this->_sshtoolsParams as $key => $val) {
            $prefval = $GLOBALS['prefs']->getValue($val['pref']);
            $params[$key] = ($val['bool']) ? (($prefval) ? 'true' : 'false') : $prefval;
        }

        return '<applet code="com.sshtools.sshterm.SshTermApplet" width="600" height="500" codebase="jar" archive="SSHTermApplet-signed.jar,SSHTermApplet-jdkbug-workaround-signed.jar,SSHTermApplet-jdk1.3.1-dependencies-signed.jar">' .
               $this->_generateParamTags($params) .
               '</applet>';
    }

}
