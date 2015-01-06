/**
 * Facebook client javascript.
 *
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl LGPL-2
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
     * opts.filter
     * opts.count
     *
     */
    initialize: function(opts)
    {
        this.opts = Object.extend({
            refreshrate: 300,
            count: 10,
            filter: 'nf'
        }, opts);

        this.getNewEntries();
        $(this.opts.getmore).observe('click', function(e) { this.getOlderEntries(); e.stop(); }.bind(this));
        $(this.opts.button).observe('click', function(e) { this.updateStatus(); e.stop(); }.bind(this));
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
        if (!$F(this.opts.input)) {
            return;
        }
        $(this.opts.spinner).toggle();
        var params = {
            statusText: $F(this.opts.input),
            instance: this.opts.instance
        };
        HordeCore.doAction('facebookUpdateStatus',
            params,
            { callback: this._updateStatusCallback.bind(this) }
        );
    },

    _updateStatusCallback: function(r)
    {
        $(this.opts.input).value = '';
        $(this.opts.spinner).toggle();
        $(this.opts.content).insert({ 'top': r });
    },

    addLike: function(post_id)
    {
        $(this.opts.spinner).toggle();
        var params = {
          post_id: post_id,
          instance: this.opts.instance
        };
        HordeCore.doAction('facebookAddLike',
            params,
            { callback: this._addLikeCallback.curry(post_id).bind(this) }
        );
    },

    _addLikeCallback: function(post_id, r)
    {
        $('fb' + post_id).update(r);
        $(this.opts.spinner).toggle();
    },

    getOlderEntries: function() {
        var params = {
            'newest': this.oldest,
            'instance': this.opts.instance,
            'filter': this.opts.filter
        };
        HordeCore.doAction('facebookGetStream',
            params,
            { callback: this._getOlderEntriesCallback.bind(this) }
        );
    },

    _getOlderEntriesCallback: function(response)
    {
        var content = response.c,
            h = $(this.opts.content).scrollHeight;
        this.oldest = response.o;
        $(this.opts.content).insert(content);
        $(this.opts.content).scrollTop = h;
    },

    getNewEntries: function()
    {
        var params = {
            'notifications': this.opts.notifications,
            'oldest': this.oldest,
            'newest': this.newest,
            'instance': this.opts.instance,
            'filter': this.opts.filter
        };
        HordeCore.doAction('facebookGetStream',
            params,
            { callback: this._getNewEntriesCallback.bind(this) }
        );
    },

    _getNewEntriesCallback: function(response)
    {
        $(this.opts.content).insert({ 'top': response.c });
        $(this.opts.notifications).update(response.nt);

        this.newest = response.n;
        if (!this.oldest) {
            this.oldest = response.o;
        }
    }

});
