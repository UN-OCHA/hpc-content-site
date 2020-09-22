[![Develop - build Status](https://travis-ci.com/UN-OCHA/gho8-site.svg?token=q5DydpJDYUBJoayLktvd&branch=develop)](https://travis-ci.com/UN-OCHA/gho8-site)
[![Main - build Status](https://travis-ci.com/UN-OCHA/gho8-site.svg?token=q5DydpJDYUBJoayLktvd&branch=main)](https://travis-ci.com/UN-OCHA/gho8-site)
![Build docker image](https://github.com/UN-OCHA/gho8-site/workflows/Build%20docker%20image/badge.svg)

Global Humanitarian Overview (GHO) site - Drupal 8 version
==========================================================

This is the drupal 8 codebase for the [Global Humanitarian Overview](https://gho.unocha.org) site.

Content
-------

**Note:** This is temporary and serves as a starting point. The content will
have to be better modelled once we know more about the actual content to put
inside the site.

The site has 2 types of content: `public pages` and `private pages`.
The site also contains `images` and `documents`, managed as `media` entities.
All the files are private but images on public pages are accessible to all.

**Pages and Paragraphs**

The public and private pages contain a unique field that can accept different
types of paragraphs like hero image, text, links and even a page title
paragraph. Those paragraphs can be arranged via [*layout paragraphs*](https://www.drupal.org/project/layout_paragraphs) to define multi columns sections or image grids for example.


Themes
------

The site uses the DSS common design and views and paragraphs for the content.

Theme customizations are in the
[Common design subtheme](html/themes/custom/common_design_subtheme).

The site also has an administration sub-theme extending the `seven` theme and
providing a few tweaks to the admin interface like full width node forms:
[Common design admin subtheme](html/themes/custom/common_design_admin_subtheme).

Modules
-------

The main contrib modules for this site are the [paragraphs](https://www.drupal.org/project/paragraphs) related ones (see
[composer file](composer.json).

In addition, the site has several custom modules:

- [**GHO Access**](html/modules/custom/gho_access)

  The gho_access module provides granular view permissions for node and media
  entities as well as handling the access to images on public pages, and a
  permission to assign roles.

- [**GHO General**](html/modules/custom/gho_general)

  The gho_general module provides tests of the site as well as general
  customizations like "add content" action links for the different content
  admin pages (`/admin/content`)

- [**GHO Layouts**](html/modules/custom/gho_layouts)

  The gho_layouts module provides addition layouts to use with modules relying
  on the Layout API to arrange display (ex: layout builder module or
  [layout_paragraphs](https://www.drupal.org/project/layout_paragraphs) in the
  case of GHO). This module provides notably a layout plugin to handle
  configurable grids with any number of areas.

The site has 2 more custom module that could/should be separated from the GHO
codebase to be independent module that other sites could use:

- [**Paragraphs - Page title**](html/modules/custom/paragraphs_page_title)

  The paragraphs_page_title module provides a paragraph type and associated
  theme, template and preprocess function to display the page title in a
  similar fashion to the `page_title` block.

  In the case of GHO, this is used, in combination to an image paragraph type,
  to display a hero image followed by the page title, allowing the content of
  the GHO node private and public pages to be fully managed and structured via
  paragraphs.

  Note: when using this module the `page_title` block visibility for the
  content type using page title paragraphs should be changed so that page titles
  don't appear multiple times.

- [**Linked Responsive Image Media Formatter**](html/modules/custom/linked_responsive_image_media_formatter)

  The linked_responsive_image_media_formatter module, in addition to competing
  for the longest module name, provides a formatter for image media types. This
  formatter can be used to display the image using responsive image styles and
  with extended linking options: link to content, link to media, link to image
  and custom link that can use `tokens`. It also offers the opion to set a
  custom `alt` text using `tokens` as well and an option to display the image
  as background for the link, using the `alt` text as text for the link.

  In the case of GHO, there is a `image link` paragraph type with a media
  reference field (image media) and a link field. The formatter for the image
  media is configured to have the image linking to the URL from the link field
  using a token, and to use the link field text as alt text via a token as well.

  This paragraph type is currently not in use and serves as a reference.

Notes
-----

Some notes related to the initial installation and development are available in
the [notes.md](notes.md) file.

Todo
----

- [ ] Test translations

Local development
-----------------

The site is docker based. See https://github.com/UN-OCHA/gho-stack for instructions.

To build an image run `make`. This will create a `gho8-site:local` image usable
with the local setup described in the `gho-stack` repository.

Local testing
-------------

**With Docksal**

Note: Replace `test.gho8-site.docksal` below with the appriate hostname for
your local site (ex: `gho8.test`).

```bash
mkdir -p ./html/sites/test
cp ./.travis/local/* ./html/sites/test/

fin db create test
fin drush --uri=test.gho8-site.docksal si minimal -y
fin drush --uri=test.gho8-site.docksal cset system.site uuid $(grep uuid ./config/system.site.yml | awk '{print $2}') -y
fin drush --uri=test.gho8-site.docksal cim -y
fin drush --uri=test.gho8-site.docksal cr

fin drush --uri=test.gho8-site.docksal en yaml_content -y
fin drush --uri=test.gho8-site.docksal yaml-content-import /var/www/.travis/
```

Run tests using docksal

```bash
fin exec DTT_BASE_URL=http://test.gho8-site.docksal/ ./vendor/bin/phpunit --debug --colors --testsuite=existing-site,existing-site-javascript --printer '\Drupal\Tests\Listeners\HtmlOutputPrinter'
```
