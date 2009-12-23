<?php
/**
 * The Horde_Block_twitter class provides an applet for posting tweets to Twitter.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Horde_Block
 */

if (@include_once 'Services/Twitter.php') {
    $block_name = _("Twitter Status Update");
}

require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';

class Horde_Block_Horde_twitter extends Horde_Block {

    /**
     * Whether this block has changing content.
     */
    var $updateable = false;

    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
    	if (!empty($this->_params['username'])) {
    		return sprintf(_("Twitter Status Update for %s"), $this->_params['username']);
    	}
        return _("Twitter Status Update");
    }

    function _params()
    {
        if (!@include_once 'Services/Twitter.php') {
            Horde::logMessage('The Twitter block will not work without Services_Twitter from PEAR. Run pear install Services_Twitter.',
                              __FILE__, __LINE__, PEAR_LOG_ERR);
            return array(
                'error' => array(
                    'type' => 'error',
                    'name' => _("Error"),
                    'default' => _("Internal error: Twitter block not available.")
                )
            );
        } else {
            global $conf;

            return array(
                'username' => array(
                    'type' => 'text',
                    'name' => _("Twitter Username"),
					'required' => true,
                ),
                'password' => array(
                    'type' => 'password',
                    'name' => _("Twitter Password"),
					'required' => true,
                )
            );
        }
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        if (!@include_once 'Services/Twitter.php') {
            Horde::logMessage('The Twitter block will not work without Services_Twitter from PEAR. Run pear install Services_Twitter.',
                              __FILE__, __LINE__, PEAR_LOG_ERR);
            return _("Twitter block not available. Details have been logged for the administrator.");
        }

        global $conf;

        if (empty($this->_params['username']) ||
		    empty($this->_params['password'])) {
            return _("Must configure a Twitter username and password to use this block.");
        }
		
		// Store the username and password in the session to enable
		// services/twitterapi.php's functionality.
		$_SESSION['horde']['twitterblock']['username'] = $this->_params['username'];
        $_SESSION['horde']['twitterblock']['password'] = $this->_params['password'];
		
        // Get a unique instance ID in case someone likes to have multiple
		// Twitter blocks.
        $instance = md5(serialize(array($this->_params['username'],
		                                $this->_params['password'])));

        $endpoint = Horde::url('services/twitterapi.php', true);
        $spinner = '$(\'' . $instance . '_loading\')';
		$inputNode = '$(\'' . $instance . '_newStatus\')';
		$notifyNode = '$(\'' . $instance . '_notifications\')';
        $html = <<<EOF
        <script type="text/javascript">
        function updateStatus(statusText)
        {
            {$spinner}.toggle();
            params = new Object();
            params.actionID = 'updateStatus';
            params.statusText = statusText;
            new Ajax.Updater({success:'currentStatus'},
                 '$endpoint',
                 {
                     method: 'post',
                     parameters: params,
                     onComplete: function(response) {
						e = {$notifyNode}
						e.innerHTML = response.responseText;
						setTimeout('e.innerHTML= ""', 15000);
						{$inputNode}.value = 'What are you working on now?';
						{$spinner}.toggle()
					 },
                     onFailure: function() {{$spinner}.toggle()}
                 }
           );
        }

        </script>
EOF;
        $html .= '<div id="' . $instance . '_notifications"></div>'
		       . '<input style="width:98%;margin-top:4px;margin-bottom:4px;" type="text" id="' . $instance . '_newStatus" name="' . $instance . '_newStatus" value="What are you working on?" />'
               . '<div class="fbaction"><a class="fbbutton" onclick="updateStatus($F(\'' . $instance . '_newStatus\'));" href="#">' . _("Update") . '</a></div>'
               . Horde::img('loading.gif', '', array('id' => $instance . '_loading', 'style' => 'display:none;'));
        
        return $html;
    }

}
