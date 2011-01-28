<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
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
class Horde_View_Helper_Url extends Horde_View_Helper_Base
{
    /**
     * Returns the URL for the set of +options+ provided. This takes the
     * same options as url_for in ActionController (see the
     * documentation for ActionController::Base#url_for). Note that by default
     * <tt>:only_path</tt> is <tt>true</tt> so you'll get the relative /controller/action
     * instead of the fully qualified URL like http://example.com/controller/action.
     *
     * When called from a view, url_for returns an HTML escaped url. If you
     * need an unescaped url, pass :escape => false in the +options+.
     *
     * ==== Options
     * * <tt>:anchor</tt> -- specifies the anchor name to be appended to the path.
     * * <tt>:only_path</tt> --  if true, returns the relative URL (omitting the protocol, host name, and port) (<tt>true</tt> by default unless <tt>:host</tt> is specified)
     * * <tt>:trailing_slash</tt> --  if true, adds a trailing slash, as in "/archive/2005/". Note that this
     *   is currently not recommended since it breaks caching.
     * * <tt>:host</tt> -- overrides the default (current) host if provided
     * * <tt>:protocol</tt> -- overrides the default (current) protocol if provided
     * * <tt>:user</tt> -- Inline HTTP authentication (only plucked out if :password is also present)
     * * <tt>:password</tt> -- Inline HTTP authentication (only plucked out if :user is also present)
     * * <tt>:escape</tt> -- Determines whether the returned URL will be HTML escaped or not (<tt>true</tt> by default)
     *
     * ==== Relying on named routes
     *
     * If you instead of a hash pass a record (like an Active Record or Active Resource) as the options parameter,
     * you'll trigger the named route for that record. The lookup will happen on the name of the class. So passing
     * a Workshop object will attempt to use the workshop_path route. If you have a nested route, such as
     * admin_workshop_path you'll have to call that explicitly (it's impossible for url_for to guess that route).
     *
     * ==== Examples
     *   <%= url_for(:action => 'index') %>
     *   # => /blog/
     *
     *   <%= url_for(:action => 'find', :controller => 'books') %>
     *   # => /books/find
     *
     *   <%= url_for(:action => 'login', :controller => 'members', :only_path => false, :protocol => 'https') %>
     *   # => https://www.railsapplication.com/members/login/
     *
     *   <%= url_for(:action => 'play', :anchor => 'player') %>
     *   # => /messages/play/#player
     *
     *   <%= url_for(:action => 'checkout', :anchor => 'tax&ship') %>
     *   # => /testing/jump/#tax&amp;ship
     *
     *   <%= url_for(:action => 'checkout', :anchor => 'tax&ship', :escape => false) %>
     *   # => /testing/jump/#tax&ship
     *
     *   <%= url_for(Workshop.new) %>
     *   # relies on Workshop answering a new_record? call (and in this case returning true)
     *   # => /workshops
     *
     *   <%= url_for(@workshop) %>
     *   # calls @workshop.to_s
     *   # => /workshops/5
     *
     * @return  string
     */
    public function urlFor($first = array(), $second = array())
    {
        return is_string($first) ? $first : $this->controller->getUrlWriter()->urlFor($first, $second);
    }

    /**
     * Creates a link tag of the given +name+ using a URL created by the set of
     * +options+. See the valid options in the documentation for url_for. It's
     * also possible to pass a string instead of an options hash to get a link
     * tag that uses the value of the string as the href for the link, or use
     * +:back+ to link to the referrer - a JavaScript back link will be used in
     * place of a referrer if none exists. If nil is passed as a name, the link
     * itself will become the name.
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
     * behavior, your should check for it in your controller's action by using
     * the request object's methods for post?, delete? or put?.
     *
     * You can mix and match the +html_options+ with the exception of :popup and
     * :method which will raise an ActionView::ActionViewError exception.
     *
     * ==== Examples
     *   link_to "Visit Other Site", "http://www.rubyonrails.org/", :confirm => "Are you sure?"
     *   # => <a href="http://www.rubyonrails.org/" onclick="return confirm('Are you sure?');">Visit Other Site</a>
     *
     *   link_to "Help", { :action => "help" }, :popup => true
     *   # => <a href="/testing/help/" onclick="window.open(this.href);return false;">Help</a>
     *
     *   link_to "View Image", { :action => "view" }, :popup => ['new_window_name', 'height=300,width=600']
     *   # => <a href="/testing/view/" onclick="window.open(this.href,'new_window_name','height=300,width=600');return false;">View Image</a>
     *
     *   link_to "Delete Image", { :action => "delete", :id => @image.id }, :confirm => "Are you sure?", :method => :delete
     *   # => <a href="/testing/delete/9/" onclick="if (confirm('Are you sure?')) { var f = document.createElement('form');
     *        f.style.display = 'none'; this.parentNode.appendChild(f); f.method = 'POST'; f.action = this.href;
     *        var m = document.createElement('input'); m.setAttribute('type', 'hidden'); m.setAttribute('name', '_method');
     *        m.setAttribute('value', 'delete'); f.appendChild(m);f.submit(); };return false;">Delete Image</a>
     */
    public function linkTo($name, $options = array(), $htmlOptions = array())
    {
        $url = $this->urlFor($options);

        if ($htmlOptions) {
            $href = isset($htmlOptions['href']) ? $htmlOptions['href'] : null;
            // @todo convert_options_to_javascript!(html_options, url)
            $tagOptions = $this->tagOptions($htmlOptions);
        } else {
            $tagOptions = null;
        }

        $hrefAttr = isset($href) ? null : 'href="' . $url . '"';
        $nameOrUrl = isset($name) ? $name : $url;
        return '<a ' . $hrefAttr . $tagOptions . '>' . $nameOrUrl . '</a>';
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
     * Creates a mailto link tag to the specified +email_address+, which is
     * also used as the name of the link unless +name+ is specified. Additional
     * HTML attributes for the link can be passed in +html_options+.
     *
     * mail_to has several methods for hindering email harvestors and customizing
     * the email itself by passing special keys to +html_options+.
     *
     * ==== Options
     * * <tt>encode</tt>  - This key will accept the strings "javascript" or "hex".
     *   Passing "javascript" will dynamically create and encode the mailto: link then
     *   eval it into the DOM of the page. This method will not show the link on
     *   the page if the user has JavaScript disabled. Passing "hex" will hex
     *   encode the +email_address+ before outputting the mailto: link.
     * * <tt>replace_at</tt>  - When the link +name+ isn't provided, the
     *   +email_address+ is used for the link label. You can use this option to
     *   obfuscate the +email_address+ by substituting the @ sign with the string
     *   given as the value.
     * * <tt>replace_dot</tt>  - When the link +name+ isn't provided, the
     *   +email_address+ is used for the link label. You can use this option to
     *   obfuscate the +email_address+ by substituting the . in the email with the
     *   string given as the value.
     * * <tt>subject</tt>  - Preset the subject line of the email.
     * * <tt>body</tt> - Preset the body of the email.
     * * <tt>cc</tt>  - Carbon Copy addition recipients on the email.
     * * <tt>bcc</tt>  - Blind Carbon Copy additional recipients on the email.
     *
     * ==== Examples
     *   mailTo("me@domain.com")
     *   # => <a href="mailto:me@domain.com">me@domain.com</a>
     *
     *   mailTo("me@domain.com", "My email", array('encode' => "javascript"))
     *   # => <script type="text/javascript">eval(unescape('%64%6f%63...%6d%65%6e'))</script>
     *
     *   mailTo("me@domain.com", "My email", array('encode' => "hex"))
     *   # => <a href="mailto:%6d%65@%64%6f%6d%61%69%6e.%63%6f%6d">My email</a>
     *
     *   mailTo("me@domain.com", null, array('replaceAt' => "_at_", 'replaceDot' => "_dot_", 'class' => "email"))
     *   # => <a href="mailto:me@domain.com" class="email">me_at_domain_dot_com</a>
     *
     *   mailTo("me@domain.com", "My email", array('cc' => "ccaddress@domain.com",
     *            'subject' => "This is an example email"))
     *   # => <a href="mailto:me@domain.com?cc=ccaddress@domain.com&subject=This%20is%20an%20example%20email">My email</a>
     */
    public function mailTo($emailAddress, $name = null, $htmlOptions = array())
    {
        // extra options "cc", "bcc", "subject", "body"
        $extras = '';
        $extraParts = array('cc', 'bcc', 'body', 'subject');
        foreach ($extraParts as $partName) {
            if (isset($htmlOptions[$partName])) {
                $partValue = str_replace('+', '%20', urlencode($htmlOptions[$partName]));
                $extras .= "{$partName}={$partValue}&";
            }
            unset($htmlOptions[$partName]);
        }
        if (! empty($extras)) {
            $extras = '?' . rtrim($extras, '&');
        }

        // obfuscation options "replaceAt" and "replaceDot"
        $emailAddressObfuscated = $emailAddress;
        foreach (array('replaceAt' => '@', 'replaceDot' => '.') as $option => $find) {
            if (isset($htmlOptions[$option])) {
                $emailAddressObfuscated = str_replace($find,
                                                      $htmlOptions[$option],
                                                      $emailAddressObfuscated);
            }
            unset($htmlOptions[$option]);
        }

        $string = '';

        $encode = isset($htmlOptions['encode']) ? $htmlOptions['encode'] : null;
        unset($htmlOptions['encode']);

        if ($encode == 'javascript') {
            $name = isset($name) ? $name : $emailAddress;
            $htmlOptions = array_merge($htmlOptions,
                                       array('href' => "mailto:{$emailAddress}{$extras}"));
            $tag = $this->contentTag('a', $name, $htmlOptions);

            foreach (str_split("document.write('$tag');") as $c) {
                $string .= sprintf("%%%x", ord($c));
            }

            return "<script type=\"text/javascript\">eval(unescape('$string'))</script>";
        } elseif ($encode == 'hex') {
            $emailAddressEncoded = '';
            foreach (str_split($emailAddressObfuscated) as $c) {
                $emailAddressEncoded .= sprintf("&#%d;", ord($c));
            }

            foreach (str_split('mailto:') as $c) {
                $string .= sprintf("&#%d;", ord($c));
            }

            foreach (str_split($emailAddress) as $c) {
                if (preg_match('/\w/', $c)) {
                    $string .= sprintf("%%%x", ord($c));
                } else {
                    $string .= $c;
                }
            }
            $name = isset($name) ? $name : $emailAddressEncoded;
            $htmlOptions = array_merge($htmlOptions,
                                       array('href' => $string . $extras));
            return $this->contentTag('a', $name, $htmlOptions);

        } else {
            $name = isset($name) ? $name : $emailAddressObfuscated;
            $htmlOptions = array_merge($htmlOptions,
                                       array('href' => "mailto:{$emailAddress}{$extras}"));
            return $this->contentTag('a', $name, $htmlOptions);
        }
    }

    /**
     * True if the current request URI was generated by the given +options+.
     *
     * ==== Examples
     * Let's say we're in the <tt>/shop/checkout</tt> action.
     *
     *   current_page?(:action => 'process')
     *   # => false
     *
     *   current_page?(:controller => 'shop', :action => 'checkout')
     *   # => true
     *
     *   current_page?(:action => 'checkout')
     *   # => true
     *
     *   current_page?(:controller => 'library', :action => 'checkout')
     *   # => false
     *
     *  @todo finish implementation
     */
    public function isCurrentPage($options)
    {
        $urlString = htmlentities($this->urlFor($options));
        if (preg_match('/^\w+:\/\//', $urlString)) {
            // @todo implement
            // url_string == "#{request.protocol}#{request.host_with_port}#{request.request_uri}"
            throw new Horde_View_Exception('not implemented');
        } else {
            if ($this->controller) {
                // @todo prepending "/" is a hack, need to fix request object
                $request = $this->controller->getRequest();
                $requestUri = '/' . ltrim($request->getPath(), '/');
            } else {
                // @todo accessing $_REQUEST directly is a hack
                $requestUri = $_SERVER['REQUEST_URI'];
            }

            return $urlString == $requestUri;
        }
    }

}
