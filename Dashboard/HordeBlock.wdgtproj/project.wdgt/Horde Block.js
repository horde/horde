/**
 * Called by HTML body element's onload event when the widget is ready to start
 */
function load()
{
    setupParts();
}

/**
 * Called when the widget has been removed from the Dashboard
 */
function remove()
{
    // Stop any timers to prevent CPU usage
	
    // Remove preferences
    widget.setPreferenceForKey(null, createInstancePreferenceKey('url'));
    widget.setPreferenceForKey(null, createInstancePreferenceKey('username'));
}

/**
 * Called when the widget has been hidden
 */
function hide()
{
    // Stop any timers to prevent CPU usage
}

/**
 * Called when the widget has been shown
 */
function show()
{
    // Restart any timers that were stopped on hide
	
	var url = widget.preferenceForKey(instancePreferenceKey('url'));
    var username = widget.preferenceForKey(instancePreferenceKey('username'));

	var u = splitURL(url);
	var password;
	if (u) {
		password = KeychainPlugIn.getPassword(username, u.serverName, u.serverPath);
	}
}

/**
 * Called when the widget has been synchronized with .Mac
 */
function sync()
{
    // Retrieve any preference values that you need to be synchronized here
	var url = widget.preferenceForKey(instancePreferenceKey('url'));
    var username = widget.preferenceForKey(instancePreferenceKey('username'));
	
	// Retrieve password from Keychain
	var u = splitURL(url);
	var password;
	if (u) {
		password = KeychainPlugIn.getPassword(username, u.serverName, u.serverPath);
	}
	
	// If prefs are showing, update fields.
	
	// Else update displayed block.
}

/**
 * Function: showBack(event)
 * Called when the info button is clicked to show the back of the widget
 *
 * event: onClick event from the info button
 */
function showBack(event)
{
    var front = document.getElementById('front');
    var back = document.getElementById('back');

    if (window.widget) {
        widget.prepareForTransition('ToBack');
    }

    front.style.display = 'none';
    back.style.display = 'block';

    if (window.widget) {
        setTimeout('widget.performTransition();', 0);
    }
	
	var url = widget.preferenceForKey(instancePreferenceKey('url'));
	if (typeof url != 'undefined' && url != '') {
		document.getElementById('horde_url').value = url;
	}
	
	var username = widget.preferenceForKey(instancePreferenceKey('username'));
	if (typeof username != 'undefined' && username != '') {
		document.getElementById('horde_username').value = username;
	}
	
	var u = splitURL(url);
	if (u) {
		var password = KeychainPlugIn.getPassword(username, u.serverName, u.serverPath);
		if (typeof password != 'undefined' && password != '') {
			document.getElementById('horde_password').value = password;
		}
	}
}

/**
 * Function: showFront(event)
 * Called when the done button is clicked from the back of the widget
 *
 * event: onClick event from the done button
 */
function showFront(event)
{
    var front = document.getElementById('front');
    var back = document.getElementById('back');

    if (window.widget) {
        widget.prepareForTransition('ToFront');
    }

    front.style.display = 'block';
    back.style.display = 'none';
	
	if (window.widget) {
		// Save preferences
		var url = document.getElementById('horde_url').value;
		if (url != '') { 
			widget.setPreferenceForKey(url, instancePreferenceKey('url'));
		}

		var username = document.getElementById('horde_username').value;
		if (username != '') { 
			widget.setPreferenceForKey(username, instancePreferenceKey('username'));
		}
		
		// Save password in Keychain
		var password = document.getElementById('horde_password').value;
		var u = splitURL(url);
		if (password != '' && u) {
			KeychainPlugIn.addPassword(password, username, u.serverName, u.serverPath);
		}
	}
	
    if (window.widget) {
        setTimeout('widget.performTransition();', 0);
    }
}

/**
 * Unique preference key identifier for this widget instance (allows multiple widget instances with different preferences).
 */
function instancePreferenceKey(key)
{
	return widget.identifier + '-' + key;
}

/**
 * Split a URL into server name and path
 */
function splitURL(url)
{
	if (!url) {
		return false;
	}
	var m = url.match(/\w+:\/\/([^\/]*)\/(.*)/);
	if (!m || !m.length || m.length != 3) {
		return false;
	}
	return { serverName: m[1], serverPath: m[2] };
}

if (window.widget) {
    widget.onremove = remove;
    widget.onhide = hide;
    widget.onshow = show;
    widget.onsync = sync;
}
