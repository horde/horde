<?php
/**
 * Folks Base Class.
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

class Folks {

    const VFS_PATH = '.horde/folks';

    /**
     * Returns published videos from user
     *
     * @param string $user User to check
     */
    static function format_date($time)
    {
        return strftime($GLOBALS['prefs']->getValue('date_format'), $time);
    }

    /**
     * Returns published videos from user
     *
     * @param string $user User to check
     */
    static function format_datetime($time)
    {
        return strftime($GLOBALS['prefs']->getValue('date_format'), $time)
            . ' '
            . (date($GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia', $time));
    }

    /**
     * Returns avaiable countries
     */
    static function getCountries()
    {
        try {
           return Horde::loadConfiguration('countries.php', 'countries', 'folks');
        } catch (Horde_Exception $e) {
            return Horde_Nls::getCountryISO();
        }
    }

    /**
     * Get Image path
     *
     * @param string $user       Image username
     * @param string $view       View type
     * @param boolean $full      Generate a full URL.
     */
    static public function getImageUrl($user, $view = 'small', $full = false)
    {
        if (empty($GLOBALS['conf']['images']['direct'])) {
            return Horde::url('view.php', $full)->add(array('view' => $view, 'id' => $user))->setRaw(true);
        } else {
            $p = hash('md5', $user);
            return $GLOBALS['conf']['images']['direct'] .
                   '/' . substr(str_pad($p, 2, 0, STR_PAD_LEFT), -2) . '/' . $view . '/' .
                   $p . '.' . $GLOBALS['conf']['images']['image_type'];
        }
    }

    /**
     * Return a properly formatted link depending on the global pretty url
     * configuration
     *
     * @param string $controller       The controller to generate a URL for.
     * @param array $data              The data needed to generate the URL.
     * @param boolean $full            Generate a full URL.
     * @param integer $append_session  0 = only if needed, 1 = always,
     *                                 -1 = never.
     *
     * @param string  The generated URL
     */
    function getUrlFor($controller, $data = null, $full = false, $append_session = 0)
    {
        switch ($controller) {
        case 'list':
            if (empty($GLOBALS['conf']['urls']['pretty'])) {
                return Horde::url($data . '.php', $full, $append_session);
            } else {
                return Horde::url('list/' . $data, $full, $append_session);
            }

        case 'feed':
            if (empty($GLOBALS['conf']['urls']['pretty'])) {
                return Horde::url('rss/' . $data . '.php', $full, $append_session);
            } else {
                return Horde::url('feed/' . $data, $full, $append_session);
            }

        case 'user':
            if (empty($GLOBALS['conf']['urls']['pretty'])) {
                return Horde::url('user.php', $full, $append_session)->add('user', $data);
            } else {
                return Horde::url('user/' . $data, $full, $append_session);
            }
        }
    }

    /**
     * Calculate user age
     */
    static public function calcAge($birthday)
    {
        if (substr($birthday, 0, 4) == '0000') {
            return array('age' => '', 'sign' => '');
        }

        list($year, $month, $day) = explode('-', $birthday);
        $year_diff = date('Y') - $year;
        $month_diff = date('m') - $month;
        $day_diff = date('d') - $day;

        if ($month_diff < 0) {
            $year_diff--;
        } elseif (($month_diff == 0) && ($day_diff < 0)) {
            $year_diff--;
        }

        if (empty($year_diff)) {
            return array('age' => '', 'sign' => '');
        }

        $sign = '';
        switch ($month) {

        case 1:
            $sign = ($day<21) ? _("Capricorn") : _("Aquarius");
            break;

        case 2:
            $sign = ($day<20) ? _("Aquarius") : _("Pisces");
            break;

        case 3:
            $sign = ($day<21) ? _("Pisces") : _("Aries");
            break;

        case 4:
            $sign = ($day<21) ? _("Aries") : _("Taurus");
            break;

        case 5:
            $sign = ($day<22) ? _("Taurus") : _("Gemini");
            break;

        case 6:
            $sign = ($day<22) ? _("Gemini") : _("Cancer");
            break;

        case 7:
            $sign = ($day<23) ? _("Cancer") : _("Leo");
            break;

        case 8:
            $sign = ($day<24) ? _("Leo") : _("Virgo");
            break;

        case 9:
            $sign = ($day<24) ? _("Virgo") : _("Libra");
            break;

        case 10:
            $sign = ($day<24) ? _("Libra") : _("Scorpio");
            break;

        case 11:
            $sign = ($day<23) ? _("Scorpio") : _("Sagittarius");
            break;

        case 12:
            $sign = ($day<21) ? _("Sagittarius") : _("Capricorn");
            break;

        }

        return array('age' => $year_diff, 'sign' => $sign);
    }

    /**
     * Returns a new or the current CAPTCHA string.
     */
    static public function getCAPTCHA($new = false)
    {
        global $session;

        if ($new || !$session->get('folks', 'captcha')) {
            $captcha = '';
            for ($i = 0; $i < 5; ++$i) {
                $captcha .= chr(rand(65, 90));
            }
            $session->set('folks', 'captcha', $captcha);
        }

        return $session->get('folks', 'captcha');
    }

    /**
     * Get encripted cookie login string
     *
     * @param string $string   String to encode
     * @param string $key   Key to encode with
     *
     * @return string  Encripted
     */
    static function encodeString($string, $key)
    {
        $key = substr(hash('md5', $key), 0, 24);
        $iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $string = mcrypt_ecb(MCRYPT_3DES, $key, $string, MCRYPT_ENCRYPT, $iv);
        return base64_encode($string);
    }

    /**
     * Send email with attachments
     *
     * @param string $from       From address
     * @param string $subject    Subject of message
     * @param string $body       Body of message
     * @param array  $attaches   Path of file to attach
     *
     * @return true on succes, PEAR_Error on failure
     */
    static public function sendMail($to, $subject, $body, $attaches = array())
    {
        $mail = new Horde_Mime_Mail(array(
            'body' => $body,
            'Subject' => $subject,
            'To' => $to,
            'From' => $GLOBALS['conf']['support'],
            'User-Agent' => 'Folks ' . $GLOBALS['registry']->getVersion(),
            'X-Originating-IP' => $_SERVER['REMOTE_ADDR'],
            'X-Remote-Browser' => $_SERVER['HTTP_USER_AGENT']));

        foreach ($attaches as $file) {
            if (file_exists($file)) {
                $mail->addAttachment($file, null, null, 'UTF-8');
            }
        }

        return $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
    }

    /**
     * Fetch user email
     *
     * @param string $user       Username
     *
     * @return email on succes, PEAR_Error on failure
     */
    static public function getUserEmail($user)
    {
        // We should always realy on registration data
        // $prefs = Horde_Prefs::singleton($GLOBALS['conf']['prefs']['driver'], 'horde', $registry->convertUsername($user, true), '', null, false);
        // $prefs->retrieve();
        // $email = $prefs->getValue('alternate_email') ? $prefs->getValue('alternate_email') : $prefs->getValue('from_addr');

        // If there is no email set use the registration one
        if (empty($email)) {
            if ($GLOBALS['registry']->isAuthenticated()) {
                $profile = $GLOBALS['folks_driver']->getProfile($user);
            } else {
                $profile = $GLOBALS['folks_driver']->getRawProfile($user);
            }
            if ($profile instanceof PEAR_Error) {
                return $profile;
            }

            $email = $profile['user_email'];
        }

        if (empty($email)) {
            return PEAR::raiseError(_("Cannot retrieve user email."));
        } else {
            return $email;
        }
    }

}
