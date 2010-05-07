<?php
/**
 * @package Mail
 */

require_once 'Mail.php';
require_once 'Mail/RFC822.php';
require_once 'Horde/Browser.php';
require_once 'Horde/Text/Flowed.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Headers.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Magic.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Mail.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Part.php';
$_SERVER['SERVER_NAME'] = 'mail.example.com';

class Mail_dummy extends Mail
{
    public $send_output;

    public function send($recipients, $headers, $body)
    {
        list(,$text_headers) = Mail::prepareHeaders($headers);
        $this->send_output = $text_headers . "\n\n" . $body;
    }
}
