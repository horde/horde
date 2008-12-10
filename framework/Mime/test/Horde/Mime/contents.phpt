--TEST--
MIME_Contents tests.
--FILE--
<?php

require dirname(__FILE__) . '/../MIME/Contents.php';

$_SERVER['SERVER_NAME'] = 'mail.example.com';
$message = MIME_Structure::parseTextMIMEMessage(
    file_get_contents(dirname(__FILE__) . '/contents1.eml'));
$contents = new MIME_Contents($message);

var_export($contents->getDownloadAllList());
echo "\n";
var_export($contents->getAttachmentContents());

?>
--EXPECT--
array (
  0 => '2.0',
)
array (
  0 => 
  array (
    'name' => 'Weitergeleitete Nachricht: Small message',
    'data' => 'Return-Path: <jan@horde.org>
Received: from neo.wg.de ([unix socket])
	 by neo (Cyrus v2.2.13) with LMTPA;
	 Tue, 11 Mar 2008 17:26:11 +0100
X-Sieve: CMU Sieve 2.2
Received: from localhost (localhost [127.0.0.1])
	by neo.wg.de (Postfix) with ESMTP id 142BF32B032
	for <jan@localhost.wg.de>; Tue, 11 Mar 2008 17:26:11 +0100 (CET)
Received: from neo.wg.de ([127.0.0.1])
 by localhost (neo.wg.de [127.0.0.1]) (amavisd-new, port 10024) with ESMTP
 id 02540-02 for <jan@localhost.wg.de>; Tue, 11 Mar 2008 17:26:02 +0100 (CET)
Received: from localhost (localhost [127.0.0.1])
	by neo.wg.de (Postfix) with ESMTP id 21E2532B037
	for <jan@localhost>; Tue, 11 Mar 2008 17:26:02 +0100 (CET)
Received: from 192.168.60.101 ([192.168.60.101]) by neo.wg.de (Horde
	Framework) with HTTP; Tue, 11 Mar 2008 17:26:02 +0100
Message-ID: <20080311172602.12293hbhf6ddsza0@neo.wg.de>
X-Priority: 3 (Normal)
Date: Tue, 11 Mar 2008 17:26:02 +0100
From: Jan Schneider <jan@horde.org>
To: "jan@localhost" <jan@wg.de>
Subject: Small message
MIME-Version: 1.0
Content-Type: text/plain;
	charset=ISO-8859-1;
	DelSp="Yes";
	format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: 7bit
User-Agent: Internet Messaging Program (IMP) H3 (5.0-cvs)
X-Virus-Scanned: amavisd-new at wg.de
X-Spam-Status: No, score=-4.351 required=5 tests=[ALL_TRUSTED=-1.8, AWL=0.048,
 BAYES_00=-2.599]
X-Spam-Score: -4.351
X-Spam-Level: 

Small message text.


',
  ),
)
