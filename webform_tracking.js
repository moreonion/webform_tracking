(function($) {

Drupal.behaviors.webform_tracking = {
  extra_parameters: {
    "source"   : "s",
    "medium"   : "m",
    "version"  : "v",
    "other"    : "o",
    "term"     : "t",
    "campaign" : "c",
  },
  google_analytics: {
    "source"   : "utm_source",
    "medium"   : "utm_medium",
    "term"     : "utm_term",
    "version"  : "utm_content",
    "campaign" : "utm_campaign",
  },

  attach: function(context) {
    // Run only once per page-load.
    if (context == document) {
      this.run();
    }
  },

  run: function() {
    var tracking_data = JSON.parse($.cookie('webform_tracking')) || {};
    var parameters = this.get_url_parameters();
    var base_url = Drupal.settings.webform_tracking.base_url;

    var dnt = window.navigator.doNotTrack;
    var respect_dnt = Drupal.settings.webform_tracking.respect_dnt;
    if ((dnt === "yes" || dnt == "1") && respect_dnt) {
      return;
    }

    tracking_data.user_id = tracking_data.user_id || this.new_user_id();

    // tags
    var tags = tracking_data.tags || [];
    if (typeof parameters['tag'] !== 'undefined') {
      $.merge(tags, parameters['tag'].split(','));
    }
    if (typeof parameters['tags'] !== 'undefined') {
      $.merge(tags, parameters['tags'].split(','));
    }
    tracking_data.tags = this.sort_unique(tags);

    // extra parameters
    var values = this.extra_parameters;
    for (var key in values) {
      if (values.hasOwnProperty(key)) {
        var result = undefined;
        var alias = values[key];

        if (typeof this.google_analytics[key] !== 'undefined') {
          var google = this.google_analytics[key];
        }

        result = typeof parameters[google] !== 'undefined' ? parameters[google] : result;
        result = typeof parameters[alias] !== 'undefined' ? parameters[alias] : result;
        result = typeof parameters[key] !== 'undefined' ? parameters[key]: result;

        if (typeof result !== 'undefined') {
          tracking_data[key] = result;
        }
      }
    };

    // If the referer does not start with $base_url, it's external but we
    // only take the first external referer to avoid problems with off-site
    // redirects (e.g. in payment forms).
    // if no referer is send, check if we got one via the GET-parameters
    if (typeof tracking_data['external_referer'] === 'undefined') {
      if (document.referrer.indexOf(base_url) !== 0) {
        tracking_data['external_referer'] = document.referrer;
      }
      else if (typeof parameters['external_referer'] !== 'undefined' && parameters['external_referer'].indexOf(base_url) !== 0) {
        tracking_data['external_referer'] = parameters['external_referer'];
      }
    }

    // history
    var history = tracking_data.history || [];
    var path = parameters.q || window.location.pathname.substr(1);
    if (path !== 'system/ajax') { // works with webform_ajax.
      var length = history.push(base_url + '/' + path);
      if (length > 10) {
        // If the history is getting too long, we need at least the
        // following:
        // [0] is the entry page
        // [-3] might be the last page before the form == referer
        // [-2] might be the form
        // [-1] might be the forms /done page
        // 10 is an arbitrary value, you just might want to avoid
        // calling the array functions below on every request if not
        // necessary.
        history = [history[0]];
        $.merge(history, history.slice(-3));
      }
    }
    tracking_data.history = history;

    $.cookie('webform_tracking', JSON.stringify(tracking_data));
  },
  get_url_parameters: function() {
    var parameters = {};
    var variables = window.location.search.substring(1).split('&');
    for (var i = 0; i < variables.length; i++) {
      var parameter = variables[i].split('=');
      parameters[parameter[0]] = parameter[1];
    };
    return parameters;
  },

  new_user_id: function() {
    // http://x443.wordpress.com/2012/03/18/adler32-checksum-in-javascript/
    var adler32 =  function(a,b,c,d,e,f) {
      for (b=65521,c=1,d=e=0;f=a.charCodeAt(e++); d=(d+c)%b) c=(c+f)%b;
      return(d<<16)|c
    }
    return adler32(String(Math.random() + Date.now()));
  },

  sort_unique: function(array) {
    if (!array.length) {
      return array;
    }
    var result = [array[0]];
    for (var i = 1; i < array.length; i++) {
      if (array[i-1] !== array[i]) {
        result.push(array[i]);
      }
    }
    return result;
  }
}

})(jQuery);
