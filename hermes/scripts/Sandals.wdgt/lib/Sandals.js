/**
 * This file is part of Sandals.wdgt, distributed with the Hermes application.
 * Hermes is part of the Horde Project (http://www.horde.org/)
 *
 * Sandals is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Sandals is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Horde; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * Copyright 2006-2008 Ben Klang <ben@alkaloid.net>
 *
 * serialize() based on phpSerialize
 * phpSerialize() and getObjectClass() are courtesy of:
 * http://magnetiq.com/2006/07/30/php-style-serialization-of-javascript-objects/
 *
 * unserialize() based on PHP_Unserialize
 * PHP_Unserialize (C) 2005 Richard Heyes <http://www.phpguru.org/>
 * 
 * @author Ben Klang <ben@alkaloid.net>
 */

// Global variables
var gDoneButton; 
var gInfoButton; 
var currentArea = false;
// XML-RPC object
var hermes;
// Form data arrays
var clients;
var jobTypes;
var costObjects;
// Boolean: keep track of whether we need to force a refresh of the server data
var prefsChanged;

// Connection details for Horde instance
var url = '';
var username = '';
var password = '';


if (window.widget) {
    //widget.onremove = onremove;
    //widget.onhide = onhide;
    widget.onshow = onshow;
}
//
//function onremove()
//{
//    alert("Remove");
//}
//
//function onhide()
//{
//    alert("Hide");
//}

/**
 * Seed the UI with the current date each time Dashboard is shown
 */
function onshow()
{
    var date = new Date();
    document.getElementById('date_month').value = date.getMonth() + 1;
    document.getElementById('date_day').value = date.getDate();
    document.getElementById('date_year').value = date.getFullYear();
    document.getElementById('hours').focus();
}

/**
 * Create the glowing "i" button to access the back panel
 */
function setup()
{
    try {
        var front = document.getElementById('front');
        var infoButton = document.getElementById('infoButton');
        var back = document.getElementById('back');
        var doneButton = document.getElementById('doneButton');
        // Create the Info and Done buttons
        gDoneButton = new AppleGlassButton(doneButton, "Done", hidePrefs);
        gInfoButton = new AppleInfoButton(infoButton, front, "black", "white", showPrefs); 
        onshow();
        
        // Try to load saved preferences
        if (window.widget) {
            url = widget.preferenceForKey(widget.identifier + '@url') || '';
            username = widget.preferenceForKey(widget.identifier + '@username') || '';

            // Try to use Keychain to read the password information.
            // This seems to work on OS X 10.5 only
            if (widget.KeychainPlugIn && url != '') {
                urlparts = splitURL(url);
                password = KeychainPlugIn.getPassword(username,
                                                      urlparts.serverName,
                                                      urlparts.serverPath) || '';
            } else {
                // Fall back to storing the password in preferences
                password = widget.preferenceForKey(widget.identifier + '@password') || '';
            }
            document.getElementById('horde_url').value = url;
            document.getElementById('horde_username').value = username;
            document.getElementById('horde_password').value = password;

        }
        // Disable the spinners
        document.getElementById('spinner-front').style.display = 'none';
        document.getElementById('spinner-back').style.display = 'none';
    } catch(e) {
        // Disable the spinners
        document.getElementById('spinner-front').style.display = 'none';
        document.getElementById('spinner-back').style.display = 'none';
        showMsg(e)
    }

}

/**
 * Flip to the back panel and show configuration options
 */
function showPrefs() 
{ 
    var front = document.getElementById("front"); 
    var back = document.getElementById("back"); 

    if (window.widget) {
        widget.prepareForTransition("ToBack"); 
    }

    front.style.display="none"; 
    back.style.display="block"; 

    if (window.widget) {
        setTimeout ('widget.performTransition();', 0); 
    }
}

/**
 * This function will be called to flip back to the front screen.
 * First check the user-supplied data.  Then attempt to load the
 * server data from Horde.  If no errors occur flip back to the
 * front UI. On error, show the message and do not flip back.
 */
function hidePrefs()
{
    // Clear any error messages
    showMsg();

    // Enable the spinners
    document.getElementById('spinner-front').style.display = 'inline';
    document.getElementById('spinner-back').style.display = 'inline';
    
    try {
        validateAndStore();
        // If our configuration changed refresh the server data
        if (prefsChanged) {
            refreshServerData();
        }
    } catch(e) {
        // Disable the spinners and show the error
        document.getElementById('spinner-front').style.display = 'none';
        document.getElementById('spinner-back').style.display = 'none';
        showMsg(e);
        return false;
    }

    var front = document.getElementById("front");
    var back = document.getElementById("back");

    // Disable the spinners
    document.getElementById('spinner-front').style.display = 'none';
    document.getElementById('spinner-back').style.display = 'none';

    // Flip back to the front
    if (window.widget) {
        widget.prepareForTransition("ToFront");
    }

    front.style.display="block";
    back.style.display="none";

    if (window.widget) {
        setTimeout('widget.performTransition();', 0);
    }

}

/**
 * Check the URL, username, and password supplied by the user.
 * Store this information in the widget preferences.
 * Also determine whether the preferences have changed
 * since the last time they were stored.  This may indicate that
 * a refresh of server data is required.
 * Finally, construct a URL from that data and configure the
 * XML-RPC handler.
 */
function validateAndStore()
{
    url = document.getElementById("horde_url").value || '';
    username = document.getElementById("horde_username").value || '';
    password = document.getElementById("horde_password").value || '';
    if (url == '' || username == '' || password == '') {
        //throw('You must supply the full URL to Horde\'s rpc.php as well as a valid username and password to continue.');    
        throw('Horde connection not yet configured.');
    }

    // Make sure the url has a schema.  Default to non-SSL
    if (url.indexOf('http') != 0) {
        url = "http://"+url;
    }

    // Determine if the Horde instance (url + username + password) changed
    prefsChanged = true;
    if (window.widget) {
        var oldurl = widget.preferenceForKey(widget.identifier + '@url');
        var oldusername = widget.preferenceForKey(widget.identifier + '@username');
        var oldpassword = widget.preferenceForKey(widget.identifier + '@password');

        if (url == oldurl &&
            username == oldusername &&
            password == oldpassword) {

            // Config data has not changed.
            prefsChanged = false;
        }

        // Store changed preferences
        if (prefsChanged) {
            widget.setPreferenceForKey(url, widget.identifier + "@url");
            widget.setPreferenceForKey(username, widget.identifier + "@username");
            // Try to use Keychain to store the password.
            // This seems to work on OS X 10.5 only
            if (widget.KeychainPlugIn) {
                urlparts = splitURL(url);
                KeychainPlugIn.addPassword(password, username, urlparts.serverName,
                                                   urlparts.serverPath);
            } else {
                // Fall back to preference storage
                widget.setPreferenceForKey(password, widget.identifier + "@password");
            }
        }
    }
    
    // Will hold fully-qualified URL including username and password
    var fqurl = '';
    // SSL flag
    var ssl = false;
    // Strip off 'http://' if it is there
    if (url.indexOf('http://') == 0) {
        fqurl = url.substr(7);
        ssl = false;
    } else if (url.indexOf('https://') == 0) {
        fqurl = url.substr(8);
        ssl = true;
    } else {
        fqurl = url;
    }

    var creds = '';
    // Build the URL
    if (username) {
        creds += escape(username);
        if (password)
            creds += ':' + escape(password);
        creds += '@';
    }

    if (ssl) {
        fqurl = 'https://' + creds + fqurl;
    } else {
        fqurl = 'http://' + creds + fqurl;
    }

    hermes = XMLRPC.getService(fqurl);
    hermes.add("system.listMethods", "listMethods");
    hermes.add("time.listJobTypes", "listJobTypes");
    hermes.add("time.listClients", "listClients");
    hermes.add("time.listCostObjects", "listCostObjects");
    hermes.add("time.recordTime", "recordTime");
    XMLRPC.onerror = function(e){ showMsg(e); }

    return true;
}

/**
 * Force a refresh of the server data.  This is initiated by a
 * button press on the back panel.
 */
function forceRefresh()
{
    // Clear any old errors
    showMsg();
    try {
        validateAndStore();
        refreshServerData();   
    } catch(e) { showMsg(e); }
}

/**
 * Refresh the current cache of server data, including the list of
 * clients, job types, and cost objects.  This method assumes
 * a valid XML-RPC object has been instantiated.
 * TODO: Spinner added but since this method is called synchronously it is
 * never visible.  Fix vcXMLRPC to use XMLHttpRequest and the onreadystatechange
 * callback.
 */
function refreshServerData()
{
    try {
        clients = hermes.listClients();
        jobTypes = hermes.listJobTypes();
        // TODO: use listMethods and enumerate all Horde apps providing a
        // listCostObjects method.  For now, we'll just use the deliverables
        // configured in Hermes.
        // FIXME: Iterate over clients and call once for each client.  Yes,
        // this is heavy-handed (and slow!) but it appears to be the only way
        // to get a list of deliverables valid for each client.  The
        // alternative would be to change the information returned by the
        // listCostObjects API.  Maybe for Horde 4?
        costObjects = hermes.listCostObjects(new Array());
    } catch(e) {
        // vcXMLRPC doesn't do a good job of catching 401 so we do it manually.
        // 401 (Unauthorized) is the proper error code when a username or
        // password is incorrect.  This is indeed what you get when tested in
        // Safari.  For some reason, when running under Dashboard, the status
        // is set to -1012.  My best guess is related to teh fact that
        // 1012 is the error code for XML_RNGP_DEFINE_EMPTY in libxml.
        // If that is not where the 1012 comes from, I have no idea.
        if (e.http_status == 401 || e.http_status == -1012) {
            throw "Invalid username or password.";
        }
        throw e;
    }

    // TODO: Cache this information in Dashboard prefs
    //if (window.widget) {
    //    widget.setPreferenceForKey(serialize(clients),
    //                               widget.identifier + "@clients");
    //    widget.setPreferenceForKey(serialize(jobTypes),
    //                               widget.identifier + "@jobTypes");
    //}
    
    //
    // Refresh the client list drop-down
    //
    var noClientsWarning = document.getElementById('noClients');
    var clientSelector = document.getElementById('client');

    // Since the select-boxes are slightly taller than plain text, enlarge the
    // window slightly so as not to cut off the bottom edge of the buttons.
    if (window.widget && !clientSelector.firstChild) {
        window.resizeBy(0, 4);
    }

    while (clientSelector.firstChild) {
        clientSelector.removeChild(clientSelector.firstChild);
    }
    for (client in clients) {
        // FIXME: This is a hack
        // Ignore the inherited toXMLRPC from vcXMLRPC
        if (client == 'toXMLRPC') {
            continue;
        }
        element = document.createElement('option');
        element.value = client;
        element.innerHTML = clients[client];
        clientSelector.appendChild(element);
    }
    // If we have a valid list, show the selectbox.
    if (clientSelector.firstChild) {
        noClientsWarning.style.display = 'none';
        clientSelector.style.display = '';
    } else {
        // ...otherwise show the "no clients" error
        noClientsWarning.style.display = 'inline';
        clientSelector.style.display = 'none';
    }

    //
    // Refresh the job types drop-down
    //
    var noJobTypesWarning = document.getElementById('noJobTypes');
    var jobTypeSelector = document.getElementById('jobType');
    while (jobTypeSelector.firstChild) {
        jobTypeSelector.removeChild(jobTypeSelector.firstChild);
    }
    while (jobType = jobTypes.pop()) {
        element = document.createElement('option');
        element.value = jobType.id;
        element.innerHTML = jobType.name;
        jobTypeSelector.appendChild(element);
    }
    // If we have a valid list, show the selectbox.
    if (jobTypeSelector.firstChild) {
        noJobTypesWarning.style.display = 'none';
        jobTypeSelector.style.display = '';
    } else {
        // ...otherwise show the "no jobTypess" error
        noJobTypesWarning.style.display = 'inline';
        jobTypeSelector.style.display = 'none';
    }

    //
    // Refresh the cost objects drop-down
    //
    var costObjectSelector = document.getElementById('costObject');
    while (costObjectSelector.firstChild) {
        costObjectSelector.removeChild(costObjectSelector.firstChild);
    }
    // Seed the default value
    element = document.createElement('option');
    element.value = '';
    element.innerHTML = '--- No Cost Object ---';
    costObjectSelector.appendChild(element);
    while (costObjectCategory = costObjects.pop()) {
        // Create the category header
        element = document.createElement('option');
        element.value = '';
        element.innerHTML = "---" + costObjectCategory.category + "---";
        costObjectSelector.appendChild(element);
        while (costObject = costObjectCategory.objects.pop()) {
            if (costObject.active) {
                element = document.createElement('option');
                element.value = costObject.id;
                element.innerHTML = costObject.name;
                costObjectSelector.appendChild(element);
            }
        }
    }
}

function recordTime()
{
    try {
        // date will be of the form YYYYMMDD for passing to 
        // the Horde_Date constructor
        var date = '';
        date += document.getElementById('date_year').value;
        var month = document.getElementById('date_month').value;
        // zero-pad single-digit values
        if (month < 10) {
            date += '0';
        }
        date += month;
        var mday = document.getElementById('date_day').value;
        if (mday < 10) {
            date += '0';
        }
        date += mday;
        
        var client = document.getElementById('client').value;
        var jobType = document.getElementById('jobType').value;
        var costObject = document.getElementById('costObject').value;
        var hours = document.getElementById('hours').value;
        var billable = document.getElementById('billable').value;
        var description = document.getElementById('description').value;
        var note = document.getElementById('note').value;
        
        hermes.recordTime(date, client, jobType, costObject, hours,
                          billable, description, note);
    } catch(e) {
        showMsg(e)
    }
}

function showMsg(message, level)
{
    if (message && !level) {
        level = 'warning';
    }

    var prefMsgBox = document.getElementById('prefMsgBox');
    var msgBox = document.getElementById('msgBox');
    if (!message) {
        // Clear the message box
        // Assume the msgBox and prefMsgBox have the same number of children.
        // This also allows consistent window resizing
        while (msgBox.hasChildNodes()) {
            prefMsgBox.removeChild(prefMsgBox.firstChild);
            msgBox.removeChild(msgBox.firstChild);
            if (window.widget) {
                window.resizeBy(0, -27);
            }
        }
    } else {
        // Create an li entry for the error message
        // and display the icon with the message
        if (window.widget) {
            window.resizeBy(0, 27);
        }
        msgbox = document.createElement('li');
        msgico = document.createElement('img');
        msgico.setAttribute('src', 'themes/graphics/alerts/'+level+'.png');
        prefMsgBox.appendChild(msgbox);
        prefMsgBox.lastChild.appendChild(msgico);
        prefMsgBox.lastChild.appendChild(document.createTextNode(message));

        msgbox = document.createElement('li');
        msgico = document.createElement('img');
        msgico.setAttribute('src', 'themes/graphics/alerts/'+level+'.png');
        msgBox.appendChild(msgbox);
        msgBox.lastChild.appendChild(msgico);
        msgBox.lastChild.appendChild(document.createTextNode(message));
    }
    return true;
}

function showArea(area)
{
    try {
        currentArea.style['display'] = 'hidden';
    } catch(e) {}
    currentArea = document.getElementById(area);
    currentArea.style['display'] = 'block';
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
    if (!m.length || m.length != 3) {
        return false;
    }
    return { serverName: m[1], serverPath: m[2] };
}

function resetTimeForm()
{
    document.getElementById('hours').value='';
    document.getElementById('description').value='';    
    document.getElementById('note').value='';
    showMsg();
}

/**
 * Returns the class name of the argument or 'GenericObject' if
 * it the object provenance cannot be determined.
 */
function getObjectClass(obj)
{
    if (obj && obj.constructor && obj.constructor.toString)
    {
        var arr = obj.constructor.toString().match(
            /function\s*(\w+)/);

        if (arr && arr.length == 2)
        {
            return arr[1];
        }
    }

    //return undefined;
    return 'GenericObject';
}

/**
 * Serializes the given argument, PHP-style.
 *
 * The type mapping is as follows:
 *
 * JavaScript Type    PHP Type
 * ---------------    --------
 * Number             Integer or Decimal
 * String             String
 * Boolean            Boolean
 * Array              Array
 * Object             Object
 * undefined          Null
 *
 * The special JavaScript object null also becomes PHP Null.
 * This function may not handle associative arrays or array
 * objects with additional properties well.
 */
function serialize(val)
{
    switch (typeof(val))
    {
    case "number":
        return (Math.floor(val) == val ? "i" : "d") + ":" +
            val + ";";
    case "string":
        return "s:" + val.length + ":\"" + val + "\";";
    case "boolean":
        return "b:" + (val ? "1" : "0") + ";";
    case "object":
        if (val == null)
        {
            return "N;";
        }
        else if ("length" in val)
        {
            var idxobj = { idx: -1 };

            return "a:" + val.length + ":{" + val.map(
                function (item)
                {
                    this.idx++;

                    var ser = serialize(item);

                    return ser ?
                        serialize(this.idx) + ser :
                        false;
                }, idxobj).filter(
                function (item)
                {
                    return item;
                }).join("") + "}";
        }
        else
        {
            var class_name = getObjectClass(val);

            if (class_name == undefined)
            {
                return false;
            }

            var props = new Array();

            for (var prop in val)
            {
                var ser = serialize(val[prop]);

                if (ser)
                {
                    props.push(serialize(prop) + ser);
                }
            }
            return "O:" + class_name.length + ":\"" +
                class_name + "\":" + props.length + ":{" +
                props.join("") + "}";
        }
    case "undefined":
        return "N;";
    }

    return false;
}

/**
 * Unserializes a PHP serialized data type. Currently handles:
 *  o Strings
 *  o Integers
 *  o Doubles
 *  o Arrays
 *  o Booleans
 *  o NULL
 *  o Objects
 * 
 * @param  string input The serialized PHP data
 * @return mixed        The resulting datatype
 */
function unserialize(input)
{
    var result = PHP_Unserialize_(input);
    alert(result.length);
    return result[0];
}


/**
 * Function which performs the actual unserializing
 *
 * @param string input Input to parse
 */
function PHP_Unserialize_(input)
{
    var length = 0;
    
    switch (input.charAt(0)) {
        /**
        * Array
        */
        case 'a':
            length = PHP_Unserialize_GetLength(input);
            input  = input.substr(String(length).length + 4);

            var arr   = new Array();
            var key   = null;
            var value = null;

            for (var i=0; i<length; ++i) {
                key   = PHP_Unserialize_(input);
                input = key[1];

                value = PHP_Unserialize_(input);
                input = value[1];

                arr[key[0]] = value[0];
            }

            input = input.substr(1);
            return [arr, input];
            break;
        
        /**
        * Objects
        */
        case 'O':
            length = PHP_Unserialize_GetLength(input);
            var classname = String(input.substr(String(length).length + 4, length));
            
            input  = input.substr(String(length).length + 6 + length);
            var numProperties = Number(input.substring(0, input.indexOf(':')))
            input = input.substr(String(numProperties).length + 2);

            var obj      = new Object();
            var property = null;
            var value    = null;

            for (var i=0; i<numProperties; ++i) {
                key   = PHP_Unserialize_(input);
                input = key[1];
                
                // Handle private/protected
                key[0] = key[0].replace(new RegExp('^\x00' + classname + '\x00'), '');
                key[0] = key[0].replace(new RegExp('^\x00\\*\x00'), '');

                value = PHP_Unserialize_(input);
                input = value[1];

                obj[key[0]] = value[0];
            }

            input = input.substr(1);
            return [obj, input];
            break;

        // Strings
        case 's':
            length = PHP_Unserialize_GetLength(input);
            return [String(input.substr(String(length).length + 4, length)), input.substr(String(length).length + 6 + length)];
            break;

        // Integers and doubles
        case 'i':
        case 'd':
            var num = Number(input.substring(2, input.indexOf(';')));
            return [num, input.substr(String(num).length + 3)];
            break;
        
        // Booleans
        case 'b':
            var bool = (input.substr(2, 1) == 1);
            return [bool, input.substr(4)];
            break;
        
        // Null
        case 'N':
            return [null, input.substr(2)];
            break;

        // Unsupported
        case 'o':
        case 'r':
        case 'C':
        case 'R':
        case 'U':
            showMsg('Unsupported PHP data type found!');

        // Error
        default:
            return [null, null];
            break;
    }
}


/**
 * Returns length of strings/arrays etc
 *
 * @param string input Input to parse
 */
function PHP_Unserialize_GetLength(input)
{
    input = input.substring(2);
    var length = Number(input.substr(0, input.indexOf(':')));
    return length;
}
