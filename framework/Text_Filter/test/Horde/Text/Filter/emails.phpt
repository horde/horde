--TEST--
Horde_Text_Filter_Email tests
--FILE--
<?php

require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Base.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Emails.php';

$emails = <<<EOT
Inline address test@example.com test.
Inline protocol mailto: test@example.com test with whitespace.
Inline Outlook [mailto:test@example.com] test.
Inline angle brackets <test@example.com> test.
Inline angle brackets (HTML) &lt;test@example.com&gt; test.
Inline angle brackets with mailto &lt;mailto:test@example.com&gt; test.
Inline with parameters test@example.com?subject=A%20subject&body=The%20message%20body test.
Inline protocol with parameters mailto:test@example.com?subject=A%20subject&body=The%20message%20body test.
test@example.com in front test.
At end test of test@example.com
Don't link http://test@www.horde.org/ test.
Real world example: mailto:pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com?subject=Release%20message%20from%20quarantine&body=%5b%23ptn6Pw-1%5d
EOT;

echo Horde_Text_Filter::filter($emails, 'emails', array('class' => 'pagelink'));

?>
--EXPECT--
Inline address <a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a> test.
Inline protocol mailto: <a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a> test with whitespace.
Inline Outlook [mailto:<a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a>] test.
Inline angle brackets &lt;<a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a>&gt; test.
Inline angle brackets (HTML) &lt;<a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a>&gt; test.
Inline angle brackets with mailto &lt;mailto:<a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a>&gt; test.
Inline with parameters <a class="pagelink" href="mailto:test@example.com?subject=A%20subject&body=The%20message%20body" title="New Message to test@example.com">test@example.com?subject=A%20subject&body=The%20message%20body</a> test.
Inline protocol with parameters mailto:<a class="pagelink" href="mailto:test@example.com?subject=A%20subject&body=The%20message%20body" title="New Message to test@example.com">test@example.com?subject=A%20subject&body=The%20message%20body</a> test.
<a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a> in front test.
At end test of <a class="pagelink" href="mailto:test@example.com" title="New Message to test@example.com">test@example.com</a>
Don't link http://test@www.horde.org/ test.
Real world example: mailto:<a class="pagelink" href="mailto:pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com?subject=Release%20message%20from%20quarantine&body=%5b%23ptn6Pw-1%5d" title="New Message to pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com">pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com?subject=Release%20message%20from%20quarantine&body=%5b%23ptn6Pw-1%5d</a>
