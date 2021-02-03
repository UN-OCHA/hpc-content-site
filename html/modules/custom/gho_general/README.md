Global Humanitarian Overview - General module
=============================================

This module contains general customizations and tests for the Global
Humanitarian Overview site.

Language
--------

This module provides a custom [Translation manager](src/StringTranslation/GhoTranslationManager.php) to
disable string translations on admin pages to keep the admin interface in the
site's default language (English) to ease proiding editorial help and defining
guidelines.

It also alters the rendering of the admin toolbar and contextual links to ensure
they also always use the site's default language.

Global language visibility
--------------------------

This module alters the `promoted` checkbox on the homepage from to indicate that
it controls the visibility of the a language across the site. (If the homepage
in a given language is published and "promoted" then the other pages in that
language are accessible to visitors, see `gho_access` as well).

Editorial flags
---------------

This modules also uses preprocess hooks to flag untranslated or unpublished
entities (nodes, media, paragraphs) to help managing multilingual content.


Embedded content
----------------

This modules provides a custom [Resource fetcher](src/OEmbed/GhoResourceFetcher.php)
to handle inconsistencies in the response from the Youtube oembed API.

The API, indeed, can return JSON data with the wrong mime type: html/text from
time to time.

Main navigation
---------------

This module provides a custom [Main navigation block](src/Plugin/Block/GhoMainMenuBlock.php)
to ensure proper access check on the node links which the default system menu
block doesn't do.

This is to ensure the menu only contains links for published and translated
nodes.

Space corrector filter
----------------------

This module provides a custom [Space corrector filter](src/Plugin/Filter/FilterSpaceCorrector.php)
to use in text filters to remove non-breaking spaces and consecutive spaces.


Soft footer blocks
------------------

This modules contains custom blocks to use as content of the soft footer:

- [Acknowledgements](src/Plugin/Block/AcknowledgementsBlock.php)
- [FTS](src/Plugin/Block/FTSBlock.php)
- [HumInsight](src/Plugin/Block/HumInsightBlock.php)


Add content links
-----------------

This modules provides `add content` (ex: article, term etc.) [links](gho_general.links.action.yml)
displayed on the admin content overview pages (/admin/content).
