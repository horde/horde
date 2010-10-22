<?php
/**
 * Email
 */
class Horde_Form_Type_Email extends Horde_Form_Type {

    /**
     * Allow multiple addresses?
     *
     * @type boolean
     * @var boolean
    */
    var $_allow_multi = false;

    /**
     * Strip domain from the address?
     *
     * @type boolean
     * @var boolean
     */
    var $_strip_domain = false;

    /**
     * Make displayed email addresses clickable?
     *
     * @type boolean
     * @var boolean
     */
    var $_link_compose = false;

    /**
     * The compose name to use
     *
     * @type text
     * @var boolean
     */
    var $_link_name;

    /**
     * The character to separate multiple email addresses
     *
     * @type text
     * @var string
     */
    var $_delimiters = ',';

    /**
     * Contact the target mail server to see if the email address is deliverable?
     *
     * @type boolean
     * @var boolean
     */
    var $_check_smtp = false;

    /**
     */
    public function init($allow_multi = false, $strip_domain = false,
                  $link_compose = false, $link_name = null,
                  $delimiters = ',')
    {
        $this->_allow_multi = $allow_multi;
        $this->_strip_domain = $strip_domain;
        $this->_link_compose = $link_compose;
        $this->_link_name = $link_name;
        $this->_delimiters = $delimiters;
    }

    /**
     */
    public function isValid($var, $vars, $value, &$message)
    {
        // Split into individual addresses.
        $emails = $this->splitEmailAddresses($value);

        // Check for too many.
        if (!$this->_allow_multi && count($emails) > 1) {
            $message = Horde_Model_Translation::t("Only one email address is allowed.");
            return false;
        }

        // Check for all valid and at least one non-empty.
        $nonEmpty = 0;
        foreach ($emails as $email) {
            if (!strlen($email)) {
                continue;
            }
            if (!$this->validateEmailAddress($email)) {
                $message = sprintf(Horde_Model_Translation::t("\"%s\" is not a valid email address."), $email);
                return false;
            }
            ++$nonEmpty;
        }

        if (!$nonEmpty && $var->required) {
            if ($this->_allow_multi) {
                $message = Horde_Model_Translation::t("You must enter at least one email address.");
            } else {
                $message = Horde_Model_Translation::t("You must enter an email address.");
            }
            return false;
        }

        return true;
    }

    /**
     * Explodes an RFC 2822 string, ignoring a delimiter if preceded
     * by a "\" character, or if the delimiter is inside single or
     * double quotes.
     *
     * @param string $string     The RFC 822 string.
     *
     * @return array  The exploded string in an array.
     */
    public function splitEmailAddresses($string)
    {
        $quotes = array('"', "'");
        $emails = array();
        $pos = 0;
        $in_quote = null;
        $in_group = false;
        $prev = null;

        if (!strlen($string)) {
            return array();
        }

        $char = $string[0];
        if (in_array($char, $quotes)) {
            $in_quote = $char;
        } elseif ($char == ':') {
            $in_group = true;
        } elseif (strpos($this->_delimiters, $char) !== false) {
            $emails[] = '';
            $pos = 1;
        }

        for ($i = 1, $iMax = strlen($string); $i < $iMax; ++$i) {
            $char = $string[$i];
            if (in_array($char, $quotes)) {
                if ($prev !== '\\') {
                    if ($in_quote === $char) {
                        $in_quote = null;
                    } elseif (is_null($in_quote)) {
                        $in_quote = $char;
                    }
                }
            } elseif ($in_group) {
                if ($char == ';') {
                    $emails[] = substr($string, $pos, $i - $pos + 1);
                    $pos = $i + 1;
                    $in_group = false;
                }
            } elseif ($char == ':') {
                $in_group = true;
            } elseif (strpos($this->_delimiters, $char) !== false &&
                      $prev !== '\\' &&
                      is_null($in_quote)) {
                $emails[] = substr($string, $pos, $i - $pos);
                $pos = $i + 1;
            }
            $prev = $char;
        }

        if ($pos != $i) {
            /* The string ended without a delimiter. */
            $emails[] = substr($string, $pos, $i - $pos);
        }

        return $emails;
    }

    /**
     * RFC(2)822 Email Parser.
     *
     * By Cal Henderson <cal@iamcal.com>
     * This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
     * http://creativecommons.org/licenses/by-sa/2.5/
     *
     * http://code.iamcal.com/php/rfc822/
     *
     * http://iamcal.com/publish/articles/php/parsing_email
     *
     * Revision 4
     *
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    public function validateEmailAddress($email)
    {
        static $comment_regexp, $email_regexp;
        if ($comment_regexp === null) {
            $this->_defineValidationRegexps($comment_regexp, $email_regexp);
        }

        // We need to strip comments first (repeat until we can't find
        // any more).
        while (true) {
            $new = preg_replace("!$comment_regexp!", '', $email);
            if (strlen($new) == strlen($email)){
                break;
            }
            $email = $new;
        }

        // Now match what's left.
        $result = (bool)preg_match("!^$email_regexp$!", $email);
        if ($result && $this->_check_smtp) {
            $result = $this->validateEmailAddressSmtp($email);
        }

        return $result;
    }

    /**
     * Attempt partial delivery of mail to an address to validate it.
     *
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    public function validateEmailAddressSmtp($email)
    {
        list(, $maildomain) = explode('@', $email, 2);

        // Try to get the real mailserver from MX records.
        if (function_exists('getmxrr') &&
            @getmxrr($maildomain, $mxhosts, $mxpriorities)) {
            // MX record found.
            array_multisort($mxpriorities, $mxhosts);
            $mailhost = $mxhosts[0];
        } else {
            // No MX record found, try the root domain as the mail
            // server.
            $mailhost = $maildomain;
        }

        $fp = @fsockopen($mailhost, 25, $errno, $errstr, 5);
        if (!$fp) {
            return false;
        }

        // Read initial response.
        fgets($fp, 4096);

        // HELO
        fputs($fp, "HELO $mailhost\r\n");
        fgets($fp, 4096);

        // MAIL FROM
        fputs($fp, "MAIL FROM: <root@example.com>\r\n");
        fgets($fp, 4096);

        // RCPT TO - gets the result we want.
        fputs($fp, "RCPT TO: <$email>\r\n");
        $result = trim(fgets($fp, 4096));

        // QUIT
        fputs($fp, "QUIT\r\n");
        fgets($fp, 4096);
        fclose($fp);

        return substr($result, 0, 1) == '2';
    }

    /**
     * RFC(2)822 Email Parser.
     *
     * By Cal Henderson <cal@iamcal.com>
     * This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
     * http://creativecommons.org/licenses/by-sa/2.5/
     *
     * http://code.iamcal.com/php/rfc822/
     *
     * http://iamcal.com/publish/articles/php/parsing_email
     *
     * Revision 4
     *
     * @param string &$comment The regexp for comments.
     * @param string &$addr_spec The regexp for email addresses.
     */
    protected function _defineValidationRegexps(&$comment, &$addr_spec)
    {
        /**
         * NO-WS-CTL       =       %d1-8 /         ; US-ASCII control characters
         *                         %d11 /          ;  that do not include the
         *                         %d12 /          ;  carriage return, line feed,
         *                         %d14-31 /       ;  and white space characters
         *                         %d127
         * ALPHA          =  %x41-5A / %x61-7A   ; A-Z / a-z
         * DIGIT          =  %x30-39
         */
        $no_ws_ctl  = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
        $alpha      = "[\\x41-\\x5a\\x61-\\x7a]";
        $digit      = "[\\x30-\\x39]";
        $cr         = "\\x0d";
        $lf         = "\\x0a";
        $crlf       = "($cr$lf)";

        /**
         * obs-char        =       %d0-9 / %d11 /          ; %d0-127 except CR and
         *                         %d12 / %d14-127         ;  LF
         * obs-text        =       *LF *CR *(obs-char *LF *CR)
         * text            =       %d1-9 /         ; Characters excluding CR and LF
         *                         %d11 /
         *                         %d12 /
         *                         %d14-127 /
         *                         obs-text
         * obs-qp          =       "\" (%d0-127)
         * quoted-pair     =       ("\" text) / obs-qp
         */
        $obs_char       = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
        $obs_text       = "($lf*$cr*($obs_char$lf*$cr*)*)";
        $text           = "([\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";
        $obs_qp         = "(\\x5c[\\x00-\\x7f])";
        $quoted_pair    = "(\\x5c$text|$obs_qp)";

        /**
         * obs-FWS         =       1*WSP *(CRLF 1*WSP)
         * FWS             =       ([*WSP CRLF] 1*WSP) /   ; Folding white space
         *                         obs-FWS
         * ctext           =       NO-WS-CTL /     ; Non white space controls
         *                         %d33-39 /       ; The rest of the US-ASCII
         *                         %d42-91 /       ;  characters not including "(",
         *                         %d93-126        ;  ")", or "\"
         * ccontent        =       ctext / quoted-pair / comment
         * comment         =       "(" *([FWS] ccontent) [FWS] ")"
         * CFWS            =       *([FWS] comment) (([FWS] comment) / FWS)
         *
         * @note: We translate ccontent only partially to avoid an
         * infinite loop. Instead, we'll recursively strip comments
         * before processing the input.
         */
        $wsp        = "[\\x20\\x09]";
        $obs_fws    = "($wsp+($crlf$wsp+)*)";
        $fws        = "((($wsp*$crlf)?$wsp+)|$obs_fws)";
        $ctext      = "($no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
        $ccontent   = "($ctext|$quoted_pair)";
        $comment    = "(\\x28($fws?$ccontent)*$fws?\\x29)";
        $cfws       = "(($fws?$comment)*($fws?$comment|$fws))";
        $cfws       = "$fws*";

        /**
         * atext           =       ALPHA / DIGIT / ; Any character except controls,
         *                         "!" / "#" /     ;  SP, and specials.
         *                         "$" / "%" /     ;  Used for atoms
         *                         "&" / "'" /
         *                         "*" / "+" /
         *                         "-" / "/" /
         *                         "=" / "?" /
         *                         "^" / "_" /
         *                         "`" / "{" /
         *                         "|" / "}" /
         *                         "~"
         * atom            =       [CFWS] 1*atext [CFWS]
         */
        $atext      = "($alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2e\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
        $atom       = "($cfws?$atext+$cfws?)";

        /**
         * qtext           =       NO-WS-CTL /     ; Non white space controls
         *                         %d33 /          ; The rest of the US-ASCII
         *                         %d35-91 /       ;  characters not including "\"
         *                         %d93-126        ;  or the quote character
         * qcontent        =       qtext / quoted-pair
         * quoted-string   =       [CFWS]
         *                         DQUOTE *([FWS] qcontent) [FWS] DQUOTE
         *                         [CFWS]
         * word            =       atom / quoted-string
         */
        $qtext      = "($no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
        $qcontent   = "($qtext|$quoted_pair)";
        $quoted_string  = "($cfws?\\x22($fws?$qcontent)*$fws?\\x22$cfws?)";
        $word       = "($atom|$quoted_string)";

        /**
         * obs-local-part  =       word *("." word)
         * obs-domain      =       atom *("." atom)
         */
        $obs_local_part = "($word(\\x2e$word)*)";
        $obs_domain = "($atom(\\x2e$atom)*)";

        /**
         * dot-atom-text   =       1*atext *("." 1*atext)
         * dot-atom        =       [CFWS] dot-atom-text [CFWS]
         */
        $dot_atom_text  = "($atext+(\\x2e$atext+)*)";
        $dot_atom   = "($cfws?$dot_atom_text$cfws?)";

        /**
         * domain-literal  =       [CFWS] "[" *([FWS] dcontent) [FWS] "]" [CFWS]
         * dcontent        =       dtext / quoted-pair
         * dtext           =       NO-WS-CTL /     ; Non white space controls
         *
         *                         %d33-90 /       ; The rest of the US-ASCII
         *                         %d94-126        ;  characters not including "[",
         *                                         ;  "]", or "\"
         */
        $dtext      = "($no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
        $dcontent   = "($dtext|$quoted_pair)";
        $domain_literal = "($cfws?\\x5b($fws?$dcontent)*$fws?\\x5d$cfws?)";

        /**
         * local-part      =       dot-atom / quoted-string / obs-local-part
         * domain          =       dot-atom / domain-literal / obs-domain
         * addr-spec       =       local-part "@" domain
         */
        $local_part = "($dot_atom|$quoted_string|$obs_local_part)";
        $domain     = "($dot_atom|$domain_literal|$obs_domain)";
        $addr_spec  = "($local_part\\x40$domain)";
    }

}

/**
 * Email with confirmation
 */
class Horde_Form_Type_EmailConfirm extends Horde_Form_Type {

    public function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && empty($value['original'])) {
            $message = Horde_Model_Translation::t("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = Horde_Model_Translation::t("Email addresses must match.");
            return false;
        } else {
            try {
                $parsed_email = Horde_Mime_Address::parseAddressList($value['original'], array(
                    'validate' => true
                ));
            } catch (Horde_Mime_Exception $e) {
                $message = $e->getMessage();
                return false;
            }
            if (count($parsed_email) > 1) {
                $message = Horde_Model_Translation::t("Only one email address allowed.");
                return false;
            }
            if (empty($parsed_email[0]->mailbox)) {
                $message = Horde_Model_Translation::t("You did not enter a valid email address.");
                return false;
            }
        }

        return true;
    }

}
