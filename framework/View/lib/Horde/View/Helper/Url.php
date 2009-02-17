<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * View helpers for URLs
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Url extends Horde_View_Helper
{
    /**
     * Creates a link tag of the given +name+ using a URL created by the set
     * of +options+. See the valid options in the documentation for
     * url_for. It's also possible to pass a string instead
     * of an options hash to get a link tag that uses the value of the string as the
     * href for the link, or use +:back+ to link to the referrer - a JavaScript back
     * link will be used in place of a referrer if none exists. If nil is passed as
     * a name, the link itself will become the name.
     *
     * ==== Options
     * * <tt>:confirm => 'question?'</tt> -- This will add a JavaScript confirm
     *   prompt with the question specified. If the user accepts, the link is
     *   processed normally, otherwise no action is taken.
     * * <tt>:popup => true || array of window options</tt> -- This will force the
     *   link to open in a popup window. By passing true, a default browser window
     *   will be opened with the URL. You can also specify an array of options
     *   that are passed-thru to JavaScripts window.open method.
     * * <tt>:method => symbol of HTTP verb</tt> -- This modifier will dynamically
     *   create an HTML form and immediately submit the form for processing using
     *   the HTTP verb specified. Useful for having links perform a POST operation
     *   in dangerous actions like deleting a record (which search bots can follow
     *   while spidering your site). Supported verbs are :post, :delete and :put.
     *   Note that if the user has JavaScript disabled, the request will fall back
     *   to using GET. If you are relying on the POST behavior, you should check
     *   for it in your controller's action by using the request object's methods
     *   for post?, delete? or put?.
     * * The +html_options+ will accept a hash of html attributes for the link tag.
     *
     * Note that if the user has JavaScript disabled, the request will fall back
     * to using GET. If :href=>'#' is used and the user has JavaScript disabled
     * clicking the link will have no effect. If you are relying on the POST
     * behavior, your should check for it in your controller's action by using the
     * request object's methods for post?, delete? or put?.
     */
    public function linkTo($name, $url, $htmlOptions = array())
    {
        if ($htmlOptions) {
            $href = isset($htmlOptions['href']) ? $htmlOptions['href'] : null;
            // @todo convert_otpions_to_javascript!(html_options, url)
            $tagOptions = $this->tagOptions($htmlOptions);
        } else {
            $tagOptions = null;
        }

        $hrefAttr = isset($href) ? null : 'href="' . $url . '"';
        $nameOrUrl = isset($name) ? $name : $url;
        return '<a ' . $hrefAttr . $tagOptions . '>' . $this->escape($nameOrUrl) . '</a>';
    }

    /**
     * Creates a link tag of the given +name+ using a URL created by the set of
     * +options+ unless the current request URI is the same as the links, in
     * which case only the name is returned (or the given block is yielded, if
     * one exists).  You can give link_to_unless_current a block which will
     * specialize the default behavior (e.g., show a "Start Here" link rather
     * than the link's text).
     *
     * ==== Examples
     * Let's say you have a navigation menu...
     *
     *   <ul id="navbar">
     *     <li><%= link_to_unless_current("Home", { :action => "index" }) %></li>
     *     <li><%= link_to_unless_current("About Us", { :action => "about" }) %></li>
     *   </ul>
     *
     * If in the "about" action, it will render...
     *
     *   <ul id="navbar">
     *     <li><a href="/controller/index">Home</a></li>
     *     <li>About Us</li>
     *   </ul>
     *
     * ...but if in the "home" action, it will render:
     *
     *   <ul id="navbar">
     *     <li><a href="/controller/index">Home</a></li>
     *     <li><a href="/controller/about">About Us</a></li>
     *   </ul>
     *
     * The implicit block given to link_to_unless_current is evaluated if the current
     * action is the action given.  So, if we had a comments page and wanted to render a
     * "Go Back" link instead of a link to the comments page, we could do something like this...
     *
     *    <%=
     *        link_to_unless_current("Comment", { :controller => 'comments', :action => 'new}) do
     *           link_to("Go back", { :controller => 'posts', :action => 'index' })
     *        end
     *     %>
     */
    public function linkToUnlessCurrent($name, $url, $htmlOptions = array())
    {
        return $this->linkToUnless($this->isCurrentPage($url),
                                   $name, $url, $htmlOptions);
    }

    /**
     * Creates a link tag of the given +name+ using a URL created by the set of
     * +options+ unless +condition+ is true, in which case only the name is
     * returned. To specialize the default behavior (i.e., show a login link
     * rather than just the plaintext link text), you can pass a block that
     * accepts the name or the full argument list for link_to_unless.
     *
     * ==== Examples
     *   <%= link_to_unless(@current_user.nil?, "Reply", { :action => "reply" }) %>
     *   # If the user is logged in...
     *   # => <a href="/controller/reply/">Reply</a>
     *
     *   <%=
     *      link_to_unless(@current_user.nil?, "Reply", { :action => "reply" }) do |name|
     *        link_to(name, { :controller => "accounts", :action => "signup" })
     *      end
     *   %>
     *   # If the user is logged in...
     *   # => <a href="/controller/reply/">Reply</a>
     *   # If not...
     *   # => <a href="/accounts/signup">Reply</a>
     */
    public function linkToUnless($condition, $name, $url, $htmlOptions = array())
    {
        return $condition ? $name : $this->linkTo($name, $url, $htmlOptions);
    }

    /**
     * Creates a link tag of the given +name+ using a URL created by the set of
     * +options+ if +condition+ is true, in which case only the name is
     * returned. To specialize the default behavior, you can pass a block that
     * accepts the name or the full argument list for link_to_unless (see the examples
     * in link_to_unless).
     *
     * ==== Examples
     *   <%= link_to_if(@current_user.nil?, "Login", { :controller => "sessions", :action => "new" }) %>
     *   # If the user isn't logged in...
     *   # => <a href="/sessions/new/">Login</a>
     *
     *   <%=
     *      link_to_if(@current_user.nil?, "Login", { :controller => "sessions", :action => "new" }) do
     *        link_to(@current_user.login, { :controller => "accounts", :action => "show", :id => @current_user })
     *      end
     *   %>
     *   # If the user isn't logged in...
     *   # => <a href="/sessions/new/">Login</a>
     *   # If they are logged in...
     *   # => <a href="/accounts/show/3">my_username</a>
     */
    public function linkToIf($condition, $name, $url, $htmlOptions = array())
    {
        return $this->linkToUnless(!$condition, $name, $url, $htmlOptions);
    }

    /**
     * True if the current request URI is the same as the current URL.
     *
     * @TODO Get REQUEST_URI from somewhere other than the global environment.
     */
    public function isCurrentPage($url)
    {
        return $url == $_SERVER['REQUEST_URI'];
    }

}
