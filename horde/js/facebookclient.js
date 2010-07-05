/**
 * Facebook client javascript.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde
 */

var Horde_Facebook = Class.create({

    oldest: '',
    newest: '',
    opts: {},

    /**
     * opts.spinner
     * opts.input
     * opts.refreshrate
     * opts.content
     * opts.endpoint,
     * opts.notifications
     * opts.getmore
     * opts.button
     * opts.instance
     *
     * 
     */
    initialize: function(opts)
    {
        this.opts = Object.extend({
            refreshrate: 300
        }, opts);

        this.getNewEntries();
        $(this.opts.getmore).observe('click', function() { this.getOlderEntries(); return false; }.bind(this));
        $(this.opts.button).observe('click', function() { this.updateStatus(); return false; }.bind(this));
    },
    
    /**
     * Update FB status.
     *
     * @param string statusText  The new status text.
     * @param string inputNode   The DOM Element for the input box.
     *
     * @return void
     */
    updateStatus: function()
    {
        $(this.opts.spinner).toggle();
        params = new Object();
        params.actionID = 'updateStatus';
        params.statusText = $F(this.opts.input);
        new Ajax.Updater({success:'currentStatus'},
             this.opts.endpoint,
             {
                 method: 'post',
                 parameters: params,
                 onComplete: function() {
                     $(this.opts.input).value = '';
                     $(this.opts.spinner).toggle()
                 },
                 onFailure: function() {$(this.opts.spinner).toggle()}
             }
       );
    },

    addLike: function(post_id)
    {
        $(this.opts.spinner).toggle();
        var params = {
          actionID: 'addLike',
          post_id: post_id
        };
        new Ajax.Updater(
             {success:'fb' + post_id},
             this.opts.endpoint,
             {
                 method: 'post',
                 parameters: params,
                 onComplete: function() {$(this.opts.spinner).toggle()}.bind(this),
                 onFailure: function() {$(this.opts.spinner).toggle()}.bind(this)
             }
       );

       return false;
    },

    getOlderEntries: function() {
        var params = {
            'actionID': 'getStream',
            'newest': this.oldest,
            'instance': this.opts.instance
        };
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: this._getOlderEntriesCallback.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    _getOlderEntriesCallback: function(response)
    {
        var content = response.responseJSON.c;
        this.oldest = response.responseJSON.o;
        var h = $(this.opts.content).scrollHeight
        $(this.opts.content).insert(content);
        $(this.opts.content).scrollTop = h;
    },

    getNewEntries: function()
    {
        var params = { 
            'actionID': 'getStream',
            'notifications': this.opts.notifications,
            'oldest': this.oldest,
            'newest': this.newest,
            'instance': this.opts.instance
         };
        new Ajax.Request(this.opts.endpoint, {
            method: 'post',
            parameters: params,
            onSuccess: this._getNewEntriesCallback.bind(this),
            onFailure: function() {
                $(this.opts.spinner).toggle();
            }
        });
    },

    _getNewEntriesCallback: function(response)
    {
        $(this.opts.content).insert({ 'top': response.responseJSON.c });
        $(this.opts.notifications).update(response.responseJSON.nt);

        this.newest = response.responseJSON.n;
        if (!this.oldest) {
            this.oldest = response.responseJSON.o;
        }
    }
    
});
