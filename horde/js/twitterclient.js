/**
 * Javascript based Twitter client for Horde.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde
 */
var Horde_Twitter = Class.create({
   inReplyTo: '',
   page: 1,

   /**
    * Const'r
    *
    * opts.input   The domid of the input form element.
    * opts.spinner The domid of the spinner element.
    * opts.content The main content area, where the tweets are placed.
    * opts.endpoint  The url endpoint for horde/servcies/twitter.php
    * opts.inreplyto
    * opts.strings.inreplyto
    * opts.strings.defaultText
    * opts.strings.justnow
    */
    initialize: function(opts) {
        this.opts = opts;
        $(this.opts.input).observe('focus', function() {this.clearInput()}.bind(this));
        $(this.opts.input).observe('blur', function() {
            if (!$(this.opts.input).value.length) {
                $(this.opts.input).value = this.opts.strings.defaultText;
            }
        }.bind(this));

        /* Get the first page */
        this.updateStream(1);

   },

   /**
    * Post a new tweet.
    *
    */
   updateStatus: function(statusText) {
        $(this.opts.input).stopObserving('blur');
        $(this.opts.spinner).toggle();
        params = {
            actionID: 'updateStatus',
            statusText: statusText,
            params: { 'in_reply_to_status_id': this.inReplyTo }
        };

        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onComplete: function(response) {
                this.updateCallback(response.responseJSON);
            }.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
                this.inReplyTo = '';
            }
        });
    },

    /**
     * Retweet the specifed tweet id.
     *
     */
    retweet: function(id) {
        $(this.opts.spinner).toggle();
        params = {
            actionID: 'retweet',
            tweetId: id
        };
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onComplete: function(response) {
                this.updateCallback(response.responseJSON);
            }.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
                this.inReplyTo = '';
            }
        });
    },

    /**
     * Update the timeline stream.
     *
     * @param integer page  The page number to retrieve.
     */
    updateStream: function(page) {
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: { actionID: 'getPage', 'page': page },
            onComplete: function(response) {
                var h = $(this.opts.content).scrollHeight
                $(this.opts.content).insert(response.responseText);
                // Don't scroll if it's the first request.
                if (page != 1) {
                    $(this.opts.content).scrollTop = h;
                } else {
                    $(this.opts.content).scrollTop = 0;
                }
            }.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    /**
     * Build the reply structure
     */
    buildReply: function(id, userid, usertext) {
        this.inReplyTo = id;
        $(this.opts.input).focus();
        $(this.opts.input).value = '@' + userid + ' ';
        $(this.opts.inreplyto).update(this.opts.strings.inreplyto + usertext);
    },

    /**
     * Callback for after a new tweet is posted.
     */
    updateCallback: function(response) {
       this.buildNewTweet(response);
       $(this.opts.input).value = this.opts.strings.defaultText;
       $(this.opts.spinner).toggle();
       this.inReplyTo = '';
       $(this.opts.inreplyto).update('');
    },

    /**
     * Build adnd display the node for a new tweet.
     */
    buildNewTweet: function(response) {
        var tweet = new Element('div', {'class':'fbstreamstory'});
        var tPic = new Element('div', {'style':'float:left'}).update(
            new Element('a', {'href': 'http://twitter.com/' + response.user.screen_name}).update(
                new Element('img', {'src':response.user.profile_image_url})
            )
        );
        var tBody = new Element('div', {'class':'fbstreambody'}).update(response.text);
        tBody.appendChild(new Element('div', {'class':'fbstreaminfo'}).update(this.opts.strings.justnow));
        tweet.appendChild(tPic);
        tweet.appendChild(tBody);
        $(this.opts.content).insert({top:tweet});
    },

    /**
     * Clear the input field.
     */
    clearInput: function() {
        $(this.opts.input).value = '';
    }
});