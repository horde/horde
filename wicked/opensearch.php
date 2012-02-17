<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('wicked');

// Url.
$url = Horde::url('', true);

// Name.
$name = $registry->get('name', 'wicked') . ' (' . $url . ')';

// Icon.
$icon = base64_encode(file_get_contents($registry->get('themesfs', 'wicked') . '/default/graphics/wicked.png'));

// Charset.
header('Content-Type: text/xml; charset=UTF-8');
echo <<<PAYLOAD
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
  <ShortName>$name</ShortName>
  <SearchForm>$url</SearchForm>
  <Url type="text/html"
       method="get"
       template="${url}display.php">
    <Param name="page" value="Search"/>
    <Param name="params" value="{searchTerms}"/>
  </Url>
  <Image height="16" width="16">data:image/png;base64,$icon</Image>
  <InputEncoding>UTF-8</InputEncoding>

</OpenSearchDescription>
PAYLOAD;
