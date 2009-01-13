/**
 * mailbox-dimp.js - Template used to format the rows in the message list
 * display.
 *
 * See the documentation of prototypejs - Template for the template format:
 *   http://www.prototypejs.org/api/template
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

DimpBase.message_list_template =
'<div id="#{domid}" title="#{subject}" class="#{bg_string}">' +
 '<div class="msgStatus">' +
  '<div class="msCheck"></div>' +
 '</div>' +
 '<div class="msgFrom">#{from}</div>' +
 '<div class="msgSubject">#{subject}</div>' +
 '<div class="msgDate">#{date}</div>' +
 '<div class="msgSize">#{size}</div>' +
 '<div class="clear">&nbsp;</div>' +
'</div>';
