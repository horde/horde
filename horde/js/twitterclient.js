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
   oldestId: null,
   newestId: null,
   instanceid: null,

   /**
    * Const'r
    *
    * opts.input   The domid of the input form element.
    * opts.counter The domid of the node to display chars remaining.
    * opts.spinner The domid of the spinner element.
    * opts.content The main content area, where the tweets are placed.
    * opts.endpoint  The url endpoint for horde/servcies/twitter.php
    * opts.inreplyto
    * opts.refreshrate How often to refresh the stream
    * opts.strings.inreplyto
    * opts.strings.defaultText
    * opts.strings.justnow
    * opts.getmore
    * opts.instanceid
    */
    initialize: function(opts) {
        this.opts = Object.extend({
            refreshrate: 300
        }, opts);

        $(this.opts.input).observe('focus', function() {this.clearInput()}.bind(this));
        $(this.opts.input).observe('blur', function() {
            if (!$(this.opts.input).value.length) {
                $(this.opts.input).value = this.opts.strings.defaultText;
            }
        }.bind(this));

        $(this.opts.input).observe('keyup', function() {
            $(this.opts.counter).update(140 - $F(this.opts.input).length);
        }.bind(this));

        $(this.opts.getmore).observe('click', function(e) {
            this.getOlderEntries();
            e.stop();
        }.bind(this));

        this.instanceid = opts.instanceid;
        /* Get the first page */
        this.getNewEntries();
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
            inReplyTo: this.inReplyTo
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
    getOlderEntries: function() {
        var params = {
            actionID: 'getPage',
            i: this.instanceid
        };

        if (this.oldestId) {
            params.max_id = this.oldestId;
        }
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: this._getOlderEntriesCallback.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    /**
     * Get newer entries, or the first page of entries if this is the first
     * request.
     */
    getNewEntries: function() {
        var params = {
            actionID: 'getPage',
            i: this.instanceid
        };

        if (this.newestId) {
            params.since_id = this.newestId;
        } else {
            params.page = 1;
        }
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: this._getNewEntriesCallback.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    /**
     * Callback for updateStream request. Updates display, remembers the oldest
     * id we know about.
     *
     */
    _getOlderEntriesCallback: function(response) {
        var content = response.responseJSON.c;
        this.oldestId = response.responseJSON.o;
        var h = $(this.opts.content).scrollHeight
        $(this.opts.content).insert(content);
        $(this.opts.content).scrollTop = h;
    },

    /**
     * Callback for retrieving new entries. Updates the display and remembers
     * the newest id, and possible the older id as well.
     *
     */
    _getNewEntriesCallback: function(response) {
        var content = response.responseJSON.c;
        if (response.responseJSON.n != this.newestId) {
            var h = $(this.opts.content).scrollHeight
            $(this.opts.content).insert({ 'top': content });
            // Don't scroll if it's the first request.
            if (this.newestId) {
                $(this.opts.content).scrollTop = h;
            } else {
                $(this.opts.content).scrollTop = 0;
            }
            this.newestId = response.responseJSON.n;

            // First time we've been called, record the oldest one as well.'
            if (!this.oldestId) {
                this.oldestId = response.responseJSON.o;
            }
        }
        new PeriodicalExecuter(function(pe) { this.getNewEntries(); pe.stop(); }.bind(this), this.opts.refreshrate );
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