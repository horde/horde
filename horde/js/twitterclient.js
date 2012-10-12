/**
 * Javascript based Twitter client for Horde.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
   overlay: null,

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
    * opts.previewid The domid of a preview element.
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

        $(this.instanceid + '_updatebutton').observe('click', function(e) {
            this.updateStatus($F(this.instanceid + '_newStatus'));
            e.stop();
        }.bind(this));

        $(this.instanceid + '_showcontenttab').observe('click', function(e) {
            this.showStream();
            e.stop();
        }.bind(this));

        $(this.instanceid + '_showmentiontab').observe('click', function(e) {
            this.showMentions();
            e.stop();
        }.bind(this));

        this.overlay = new Element('div', { 'class': 'hordeSmOverlay' }).update('&nbsp;');
        this.overlay.hide();
        $(this.instanceid + '_preview').insert({ 'before': this.overlay });
        $(this.instanceid + '_preview').observe('click', this.hidePreview.bind(this));
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
            onSuccess: function(response) {
                this.updateCallback(response.responseJSON);
            }.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
                this.inReplyTo = '';
            }.bind(this)
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
            onSuccess: function(response) {
                this.updateCallback(response.responseJSON);
            }.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
                this.inReplyTo = '';
            }.bind(this)
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
            }.bind(this)
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
          if (this.newestMention) {
              params.since_id = this.newestMention;
          } else {
              params.page = 1;
          }
          params.mentions = 1;
          callback = this._getNewMentionsCallback.bind(this);
        } else {
          if (this.newestId) {
              params.since_id = this.newestId;
          } else {
              params.page = 1;
          }
          callback = this._getNewEntriesCallback.bind(this);
        }

        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: callback,
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }.bind(this)
        });
    },

    showPreview: function(url)
    {
        $(this.instanceid + '_preview').clonePosition($(this.instanceid + '_preview').up());
        $(this.instanceid + '_preview').hide();
        $(this.instanceid + '_preview').update();
        $(this.instanceid + '_preview').appendChild(
            new Element('img', { 'src': url })
        );
        this.overlay.clonePosition($(this.instanceid + '_preview').up());
        this.overlay.show();
        Effect.BlindDown(this.instanceid + '_preview');

        return false;
    },

    hidePreview: function(e) {
      $(this.instanceid + '_preview').hide();
      this.overlay.hide();
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
        var h, content = response.responseJSON.c;

        if (response.responseJSON.n != this.newestId) {
            var h = $(this.opts.content).scrollHeight
            $(this.opts.content).insert({ 'top': content });
            if (this.activeTab != 'stream') {
                $(this.opts.contenttab).addClassName('hordeSmNew');
            } else {
                // Don't scroll if it's the first request.
                if (this.newestId) {
                    $(this.opts.content).scrollTop = h;
                } else {
                    $(this.opts.content).scrollTop = 0;
                }
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
     */
    _getNewMentionsCallback: function(response) {
        var h, content = response.responseJSON.c;

        if (response.responseJSON.n != this.newestMention) {
            h = $(this.opts.mentions).scrollHeight
            $(this.opts.mentions).insert({ 'top': content });
            if (this.activeTab != 'mentions') {
                $(this.opts.mentiontab).addClassName('hordeSmNew');
            } else {
                // Don't scroll if it's the first request.
                if (this.newestMention) {
                    $(this.opts.mentions).scrollTop = h;
                } else {
                    $(this.opts.mentions).scrollTop = 0;
                }
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
       if (response.error)
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
            $(this.opts.mentiontab).removeClassName('hordeSmNew');
            this.toggleTabs();
            $(this.opts.content).hide();
            // Only poll once on click, after that we rely on PeriodcalExecuter
            if (!this.oldestMention) {
                this.getNewEntries('mentions');
            }
            $(this.opts.mentions).show();
            this.activeTab = 'mentions';
        }
    },

    showStream: function()
    {
        if (this.activeTab != 'stream') {
            $(this.opts.contenttab).removeClassName('hordeSmNew');
            this.toggleTabs();
            $(this.opts.mentions).hide();
            $(this.opts.content).show();
            this.activeTab = 'stream';
        }
    },

    toggleTabs: function()
    {
        $(this.opts.contenttab).toggleClassName('horde-active');
        $(this.opts.mentiontab).toggleClassName('horde-active');
    },

    /**
     * Clear the input field.
     */
    clearInput: function() {
        $(this.opts.input).value = '';
    }
});