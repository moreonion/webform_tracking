Webform tracking
================

...collects data about your users and associates it with their
[webform](https://drupal.org/project/webform) submissions.

Collected data includes:

* External referer: external page your user came from (if any)
* Entry page: first page on your site visited by this user
* Internal referer: last page your user visited before the form
* Form url: url of the page, the submitted webform was displayed on. Might
  differ from the url of the webform itself it was embedded as a block for
  example.
* A user id, if possible

As well as the following (easily extendable) GET-parameters (set them in the
links your share!):

* tags
* source
* medium
* version
* other

Webform tracking respects Do-Not-Track by default, but  site-administrators
can choose to ignore it.

Webform tracking can be configured to wait for an event from a cookiebar by setting `webform_tracking_wait_for_event` to that event's name.

developed by [more onion](http://more-onion.com)
