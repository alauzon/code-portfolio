# sample_simpleview_feeds

This module is a sample of my work on a specific website that I did for [WorkShop](https://www.yourworkshop.com/). It
includes Custom Feeds plugins to fetch, parse, and process Sample listings and events from Simpleview. It
does not work standalone because it depends on some content types.

The basic work has been done by another developer than me.  I took it on in order to __improve or add__:
* __the speed__ as it was taking too much time and was not able to complete the task of importing all the data by using:
  * __the generator technique__ at
  [SimpleviewListingFetcher.php](https://github.com/alauzon/code-portfolio/blob/master/Drupal%208/modules/sample_simpleview_feeds/src/Feeds/Fetcher/SimpleviewListingFetcher.php)
  and at [SimpleviewEventFetcher.php](https://github.com/alauzon/code-portfolio/blob/master/Drupal%208/modules/sample_simpleview_feeds/src/Feeds/Fetcher/SimpleviewEventFetcher.php).
  * __custom Drush commands__ at [SampleSimpleviewFeedsCommands.php](https://github.com/alauzon/code-portfolio/blob/master/Drupal%208/modules/sample_simpleview_feeds/src/Commands/SampleSimpleviewFeedsCommands.php) to make sure the jobs
  could run more than the one minute limit for cron jobs under Drupal and that we can control exactly what gets run and
  when. The _Drupal 8/modules/sample_simpleview_feeds/script/sample_cron.sh_ file needs to get copied to another
  computer than the Pantheon server and will get called with the appropriate parameters in order to accomplish the feed
  jobs using the custom Drush commands.
* __the reliability__ as it was not able to run on Pantheon servers neither locally by using:
  * __the generator technique__
  * __custom Drush commands__
* __testing__ by using:
  * __a mock API module__ under _Drupal 8/modules/sample_simpleview_feeds/README.md_ that simply sends the file
  designated by the filename under the mock API path to the Fetchers.
* __Taxonomy Vocabulary mapping__:
  * The vocabularies from Simpleview (the feed) and the current website are different. The original vocabularies from
  Simpleview are imported. The terms in our vocabularies have a field that points to one or many terms in the Simpleview
  vocabulary terms. The relationships between the imported entities and our vocabulary terms are created using the
  mapping relationships between the terms in our vocabularies and the Simpleview vocabularies. That is done during the
  feed import and also after editing the mapping.  
  
DEPENDENCIES
------------
The module has one module dependency on Feeds. Additionally the configuration is dependent on
the Listing and Event content types, and the vocabularies for Listing Categories, Event Categories, Listing
Amenities, and Cities.

CONFIGURATION
-------------
The configuration files required for this module are committed to the sites/default/config
directory, as well as with this module in config/install.

SIMPLE VIEW
------------
Simpleview is providing 2 XML feeds, one for each of listings and events. The feed
endpoints are found in the src/Feeds/Fetcher/ classes. Both feeds are comprehensive data for all
of NY state, and so non-Sample listings need to be filtered out. For listings, this happens in the
fetcher class, where the regionid field is readily available. For events, it happens in the parser class,
where the custom field for region is simpler to access.

CATEGORIES
----------
All category and amenity values are mined from these feeds. In the processor classes,
each item is first cleared of all terms, then assigned the terms from the current feed.
This handles removal of terms, and also the addition of new terms if they are introduced.
It does not handle the removal of old terms no longer provided by the feed. These terms
will simply live in the Drupal site until manually deleted, but should not cause any harm.



