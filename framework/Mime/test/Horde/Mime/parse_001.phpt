--TEST--
Horde_Mime_Part::parseMessage() [Structure array] test
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Headers.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Part.php';
require_once 'Horde/String.php';
require_once 'Mail/RFC822.php';

$data = file_get_contents(dirname(__FILE__) . '/fixtures/sample_msg.txt');

print_r(Horde_Mime_Part::parseMessage($data, array('structure' => true)));

?>
--EXPECTF--
Array
(
    [parts] => Array
        (
            [0] => Array
                (
                    [parts] => Array
                        (
                        )

                    [subtype] => plain
                    [type] => text
                    [encoding] => 7bit
                    [dparameters] => Array
                        (
                        )

                    [parameters] => Array
                        (
                            [charset] => UTF-8
                            [DelSp] => Yes
                            [format] => flowed
                        )

                    [disposition] => inline
                    [contents] => Test.


                    [size] => 9
                )

            [1] => Array
                (
                    [parts] => Array
                        (
                            [0] => Array
                                (
                                    [parts] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [parts] => Array
                                                        (
                                                        )

                                                    [subtype] => plain
                                                    [type] => text
                                                    [encoding] => 7bit
                                                    [dparameters] => Array
                                                        (
                                                        )

                                                    [parameters] => Array
                                                        (
                                                            [charset] => UTF-8
                                                            [DelSp] => Yes
                                                            [format] => flowed
                                                        )

                                                    [disposition] => inline
                                                    [contents] => Test text.


                                                    [size] => 14
                                                )

                                            [1] => Array
                                                (
                                                    [parts] => Array
                                                        (
                                                        )

                                                    [subtype] => plain
                                                    [type] => text
                                                    [encoding] => 7bit
                                                    [dparameters] => Array
                                                        (
                                                            [filename] => test.txt
                                                        )

                                                    [parameters] => Array
                                                        (
                                                            [charset] => UTF-8
                                                            [name] => test.txt
                                                        )

                                                    [disposition] => attachment
                                                    [contents] => Test.

                                                    [size] => 7
                                                )

                                        )

                                    [subtype] => mixed
                                    [type] => multipart
                                    [encoding] => 7bit
                                    [dparameters] => 
                                    [parameters] => Array
                                        (
                                            [boundary] => =_8w0gkwkgk44o
                                        )

                                    [contents] => This message is in MIME format.

--=_8w0gkwkgk44o
Content-Type: text/plain; charset=UTF-8; format=flowed; DelSp=Yes
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

Test text.


--=_8w0gkwkgk44o
Content-Type: text/plain; charset=UTF-8; name=test.txt
Content-Disposition: attachment; filename=test.txt
Content-Transfer-Encoding: 7bit

Test.

--=_8w0gkwkgk44o--


                                    [size] => 392
                                )

                        )

                    [subtype] => rfc822
                    [type] => message
                    [dparameters] => 
                    [parameters] => Array
                        (
                            [name] => Forwarded Message
                        )

                    [contents] => Return-Path: <foo@example.com>
Delivered-To: test@example.com
Received: from localhost (localhost [127.0.0.1])
    by example.com (Postfix) with ESMTP id B09464F220
    for <test@example.com>; Tue,  7 Jul 2009 11:49:00 -0600 (MDT)
Message-ID: <20090707114900.4w4ksggowkc4@example.com>
Date: Tue, 07 Jul 2009 11:49:00 -0600
From: Foo <foo@example.com>
To: test@example.com
Subject: Test
User-Agent: Internet Messaging Program (IMP) H4 (5.0-git)
Content-Type: multipart/mixed; boundary="=_8w0gkwkgk44o"
MIME-Version: 1.0
Content-Transfer-Encoding: 7bit

This message is in MIME format.

--=_8w0gkwkgk44o
Content-Type: text/plain; charset=UTF-8; format=flowed; DelSp=Yes
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

Test text.


--=_8w0gkwkgk44o
Content-Type: text/plain; charset=UTF-8; name=test.txt
Content-Disposition: attachment; filename=test.txt
Content-Transfer-Encoding: 7bit

Test.

--=_8w0gkwkgk44o--


                )

            [2] => Array
                (
                    [parts] => Array
                        (
                        )

                    [subtype] => png
                    [type] => image
                    [encoding] => base64
                    [dparameters] => Array
                        (
                            [filename] => index.png
                        )

                    [parameters] => Array
                        (
                            [name] => index.png
                        )

                    [disposition] => attachment
                    [contents] => iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0
U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADoSURBVBgZBcExblNBGAbA2ceegTRBuIKO
giihSZNTcC5LUHAihNJR0kGKCDcYJY6D3/77MdOinTvzAgCw8ysThIvn/VojIyMjIyPP+bS1sUQI
V2s95pBDDvmbP/mdkft83tpYguZq5Jh/OeaYh+yzy8hTHvNlaxNNczm+la9OTlar1UdA/+C2A4tr
RCnD3jS8BB1obq2Gk6GU6QbQAS4BUaYSQAf4bhhKKTFdAzrAOwAxEUAH+KEM01SY3gM6wBsEAQB0
gJ+maZoC3gI6iPYaAIBJsiRmHU0AALOeFC3aK2cWAACUXe7+AwO0lc9eTHYTAAAAAElFTkSuQmCC
                    [size] => 466
                )

        )

    [subtype] => mixed
    [type] => multipart
    [encoding] => 7bit
    [dparameters] => 
    [parameters] => Array
        (
            [boundary] => =_k4kgcwkwggwc
        )

    [contents] => This message is in MIME format.

--=_k4kgcwkwggwc
Content-Type: text/plain; charset=UTF-8; format=flowed; DelSp=Yes
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

Test.


--=_k4kgcwkwggwc
Content-Type: message/rfc822; name="Forwarded Message"

Return-Path: <foo@example.com>
Delivered-To: test@example.com
Received: from localhost (localhost [127.0.0.1])
    by example.com (Postfix) with ESMTP id B09464F220
    for <test@example.com>; Tue,  7 Jul 2009 11:49:00 -0600 (MDT)
Message-ID: <20090707114900.4w4ksggowkc4@example.com>
Date: Tue, 07 Jul 2009 11:49:00 -0600
From: Foo <foo@example.com>
To: test@example.com
Subject: Test
User-Agent: Internet Messaging Program (IMP) H4 (5.0-git)
Content-Type: multipart/mixed; boundary="=_8w0gkwkgk44o"
MIME-Version: 1.0
Content-Transfer-Encoding: 7bit

This message is in MIME format.

--=_8w0gkwkgk44o
Content-Type: text/plain; charset=UTF-8; format=flowed; DelSp=Yes
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

Test text.


--=_8w0gkwkgk44o
Content-Type: text/plain; charset=UTF-8; name=test.txt
Content-Disposition: attachment; filename=test.txt
Content-Transfer-Encoding: 7bit

Test.

--=_8w0gkwkgk44o--


--=_k4kgcwkwggwc
Content-Type: image/png; name=index.png
Content-Disposition: attachment; filename=index.png
Content-Transfer-Encoding: base64

iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0
U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADoSURBVBgZBcExblNBGAbA2ceegTRBuIKO
giihSZNTcC5LUHAihNJR0kGKCDcYJY6D3/77MdOinTvzAgCw8ysThIvn/VojIyMjIyPP+bS1sUQI
V2s95pBDDvmbP/mdkft83tpYguZq5Jh/OeaYh+yzy8hTHvNlaxNNczm+la9OTlar1UdA/+C2A4tr
RCnD3jS8BB1obq2Gk6GU6QbQAS4BUaYSQAf4bhhKKTFdAzrAOwAxEUAH+KEM01SY3gM6wBsEAQB0
gJ+maZoC3gI6iPYaAIBJsiRmHU0AALOeFC3aK2cWAACUXe7+AwO0lc9eTHYTAAAAAElFTkSuQmCC
--=_k4kgcwkwggwc--

    [size] => 1869
)
