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
   oldestMention: null,
   newestMention: null,
   instanceid: null,
   activeTab: 'stream',

   /**
    * Const'r
    *
    * opts.input   The domid of the input form element.
    * opts.counter The domid of the node to display chars remaining.
    * opts.spinner The domid of the spinner element.
    * opts.content The main content area, where the tweets are placed.
    * opts.mentions  The domid of where the mentions stream should be placed.
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
        var callback, params = {
            actionID: 'getPage',
            i: this.instanceid
        };

        switch (this.activeTab) {
        case 'stream':
            if (this.oldestId) {
                params.max_id = this.oldestId;
            }
            callback = this._getOlderEntriesCallback.bind(this);
            break;
        case 'mentions':
            if (this.oldestMention) {
                params.max_id = this.oldestMention;
            }
            callback = this._getOlderMentionsCallback.bind(this);
            params.mentions = 1;
            break;
        }
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: callback,
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    /**
     * Get newer entries, or the first page of entries if this is the first
     * request.
     */
    getNewEntries: function(type) {
        var callback, params = {
            actionID: 'getPage',
            i: this.instanceid
        };
        if (type == 'mentions') {
          params.mentions = 1;
          callback = this._getNewMentionsCallback.bind(this);
        } else {
          callback = this._getNewEntriesCallback.bind(this);
        }
        if (this.newestId && type !== 'mentions') {
            params.since_id = this.newestId;
        } else {
            params.page = 1;
        }

        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: callback,
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    /**
     * Callback for updateStream request for older stream entries. Updates
     * display, remembers the oldest id we know about.
     *
     * @param object response  The response object from the Ajax request.
     */
    _getOlderEntriesCallback: function(response) {
        var content = response.responseJSON.c;
        if (response.responseJSON.o) {
            this.oldestId = response.responseJSON.o;
            var h = $(this.opts.content).scrollHeight
            $(this.opts.content).insert(content);
            $(this.opts.content).scrollTop = h;
        }
    },

    /**
     * Callback for updateStream request for older mentions. Updates display,
     * remembers the oldest id we know about.
     *
     * @param object response  The response object from the Ajax request.
     */
    _getOlderMentionsCallback: function(response) {
        var content = response.responseJSON.c;
        // If no more available, the oldest id will be null
        if (response.responseJSON.o) {
            this.oldestMention = response.responseJSON.o;
            var h = $(this.opts.mentions).scrollHeight
            $(this.opts.mentions).insert(content);
            $(this.opts.mentions).scrollTop = h;
        }
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
     * Callback for retrieving new mentions.
     *
     * @TODO: Implement paging, separate oldestId etc...
     */
    _getNewMentionsCallback: function(response) {
        var content = response.responseJSON.c;
        if (response.responseJSON.n != this.newestMention) {
            var h = $(this.opts.mentions).scrollHeight
            $(this.opts.mentions).insert({ 'top': content });
            // Don't scroll if it's the first request.
            if (this.newestMention) {
                $(this.opts.mentions).scrollTop = h;
            } else {
                $(this.opts.mentions).scrollTop = 0;
            }
            this.newestMention = response.responseJSON.n;

            // First time we've been called, record the oldest one as well.
            if (!this.oldestMention) {
                this.oldestMention = response.responseJSON.o;
            }
        }
        new PeriodicalExecuter(function(pe) { this.getNewEntries('mentions'); pe.stop(); }.bind(this), this.opts.refreshrate );
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
     * Build and display the node for a new tweet.
     */
    buildNewTweet: function(response) {
        var tweet = new Element('div', {'class':'hordeSmStreamstory'});
        var tPic = new Element('div', {'class':'solidbox hordeSmAvatar'}).update(
            new Element('a', {'href': 'http://twitter.com/' + response.user.screen_name}).update(
                new Element('img', {'src':response.user.profile_image_url})
            )
        );
        tPic.appendChild(
            new Element('div', { 'style': {'overflow': 'hidden' }}).update(
                new Element('a', {'href': 'http://twitter.com/' + response.user.screen_name}).update(response.user.screen_name)
            )
        );
        var tBody = new Element('div', {'class':'hordeSmStreambody'}).update(response.text);
        tBody.appendChild(new Element('div', {'class':'hordeSmStreaminfo'}).update(this.opts.strings.justnow + '<br><br>'));
        tweet.appendChild(tPic);
        tweet.appendChild(tBody);
        $(this.opts.content).insert({top:tweet});
    },

    showMentions: function()
    {
        if (this.activeTab != 'mentions') {
            this.toggleTabs();
            $(this.opts.content).hide();
            this.getNewEntries('mentions');
            $(this.opts.mentions).show();
            this.activeTab = 'mentions';
        }
    },

    showStream: function()
    {
        if (this.activeTab != 'stream') {
            this.toggleTabs();
            $(this.opts.mentions).hide();
            $(this.opts.content).show();
            this.activeTab = 'stream';
        }

    },

    toggleTabs: function()
    {
        $(this.opts.contenttab).toggleClassName('activeTab');
        $(this.opts.mentiontab).toggleClassName('activeTab');
    },

    /**
     * Clear the input field.
     */
    clearInput: function() {
        $(this.opts.input).value = '';
    }
});