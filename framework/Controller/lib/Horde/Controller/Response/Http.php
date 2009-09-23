<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Response
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Response
 */
class Horde_Controller_Response_Http extends Horde_Controller_Response_Base
{
    /**
     * Adds the specified cookie to the response.
     *
     * This method can be called multiple times to set more than one cookie or
     * to modify an already set one. Returns true if the adding was successful,
     * false otherwise.
     *
     * @param    Ismo_Core_Cookie   $cookie   the cookie object to add
     * @return   boolean                true if the adding was successful,
     *                                  false otherwise
     * @access   public
     */
    function addCookie($cookie)
    {
        if (get_class($cookie) == 'ismo_core_cookie' ||
            get_parent_class($cookie) == 'ismo_core_cookie') {
            $secure = 0;
            if ($cookie->isSecure()) {
                $secure = 1;
            }

            setcookie(  $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpire(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        $secure );

            return true;
        }

        return false;
    }

    /**
     * Deletes the specified cookie from the response.
     *
     * @param    IsmoCookie   $cookie  the cookie object to delete
     * @access   public
     */
    function deleteCookie($cookie)
    {
        if (get_class($cookie) == 'ismo_core_cookie' ||
            get_parent_class($cookie) == 'ismo_core_cookie') {
            $secure = 0;
            if ($cookie->isSecure()) {
                $secure = 1;
            }

            // set the expiration date to one hour ago
            $cookie->setExpire(time() - 3600);

            setcookie(  $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpire(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        $secure );
        }
    }

    /**
     * Adds a response header with the given name and value.
     *
     * This method allows response headers to have multiple values. Returns true
     * if the header could be added, false otherwise. False will be returned
     * f.g. when the headers have already been sent.  The replace parameter
     * indicates if an already existing header with the same name should be
     * replaced or not.
     *
     * @param    string  $name      the name of the header
     * @param    string  $value     the value of the header
     * @param    boolean $replace   should the header be replaced or not
     * @return   boolean            true if the header could be set, false
     *                              otherwise
     * @access   public
     */
    function addHeader($name, $value, $replace)
    {
        if (headers_sent()) {
            return false;
        }

        header("$name: $value", $replace);
        return true;
    }

    /**
     * Sends an error response to the client using the specified status code.
     *
     * Sends an error response to the client using the specified status code.
     * This will create a page that looks like an HTML-formatted server error
     * page containing the specifed message (if any), setting the content type
     * to "text/html", leaving cookies and other headers unmodified.
     *
     * If the headers have already been sent this method returns <b>false</b>
     * otherwise <b>true</b>. After this method the response should be
     * considered commited, i.e.  both headers and data have been sent to the
     * client.
     *
     * @todo                    decide what the error page should look like
     * @param    string  $code  the status code to use
     * @param    string  $msg   the message to show
     * @return   boolean        <b>true</b> if the error response could be
     *                          send, <b>false</b> otherwise (if the headers
     *                          have already been sent)
     * @access   public
     */
    function sendError($code, $msg = NULL)
    {
        if (headers_sent()) {
            return false;
        }

        header('HTTP/1.0 '.$code);

        // @todo what kind of HTML page should it be?
        ?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title><?php echo $code ?></title>
</head><body>
<h1><?php echo $code ?></h1>
<?php
    if ($msg != NULL)
    {
        echo $msg;
    }
?>
<hr>
</body></html>
<?php

        return true;
    }

    /**
     * Sends a temporary redirect respones to the client using the specifed
     * redirect URL.
     *
     * If the headers have already been sent this method returns <b>false</b>
     * otherwise <b>true</b>. After this method the response should be
     * considered commited.
     *
     * Examples:
     * <code>
     *   $u = new Ismo_Core_Url("http://a.b.c");
     *   $response->sendRedirect($u);
     * </code>
     * Redirects the browser to http://a.b.c using an Ismo_Core_Url instance.
     *
     * <code>
     *   $response->sendRedirect("http://d.e.f");
     * </code>
     * Redirects the browser to http://d.e.f using a string.
     *
     * @param    mixed   $location  url to redirect to, this can either be an
     *                              Ismo_Core_Url instance or a string
     * @return   boolean            <b>false</b> if the headers have already
     *                              been sent, <b>true</b> otherwise
     * @access   public
     */
    function sendRedirect($location)
    {
        if (headers_sent()) {
            return false;
        }

        if (get_class($location) == 'ismo_core_url') {
            $location = $location->toString(false);
        }

        /* so that it works correctly for IE */
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $location);
        header('Connection: close');

        return true;
    }

    /**
     * Sets the status code for this request.
     *
     * Sets the status code for this response. This method is used to set the
     * return status code when there is no error (for example, for the status
     * codes SC_OK or SC_MOVED_TEMPORARILY). If there is an error, and the
     * caller wishes to provide a message for the response, the sendError()
     * method should be used instead.
     *
     * @param    string $code    the status code to set
     * @access   public
     * @see      sendError()
     */
    function setStatus($code)
    {
        header('HTTP/1.0 ' . $code);
    }
}
