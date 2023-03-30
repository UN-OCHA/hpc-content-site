[![Develop - build Status](https://app.travis-ci.com/UN-OCHA/hpc-content-site.svg?branch=develop)](https://app.travis-ci.com/github/UN-OCHA/hpc-content-site)
[![Main - build Status](https://app.travis-ci.com/UN-OCHA/hpc-content-site.svg?branch=main)](https://app.travis-ci.com/github/UN-OCHA/hpc-content-site)
![Build docker image](https://github.com/UN-OCHA/hpc-content-site/workflows/Build%20docker%20image/badge.svg)

HPC Content Module
==========================================================

This is the drupal 9 codebase for the [HPC Content Module](https://content.hpc.tools) site.

> HPC Content Module is the content backend for the [Humanitarian Actions site](https://humanitarianaction.info).

Content
-------

The main content type for the site is the `article` (node) which is mainly
composed of **paragraphs**. Articles can contain sub-articles (nested articles).

Other content types that have their own pages are the `stories` (short text,
video and/or image) and the `achievements`. Those are mainly shown inside
articles but have pages for social media sharing purposes.

The paragraphs that can be used inside an articles are:

- `Achievement`: A reference to an `achievement` node
- `Bottom figures row`: A list of figures with a label and a value
- `Facts and figures`: A list of images with a short text
- `Further reading`: A list of external links with their source
- `Interactive content`: A graph/table/map embed from [datawrapper](https://www.datawrapper.de)
- `Needs and requirements`: A reference to a `needs and requirements` term
- `Photo gallery`: A gallery with up to 4 photos and a description
- `Section index`: Essentially a table of content with a list of articles
- `Separator`: A simple line separator
- `Signature`: USG's signature
- `Story`: A reference to a `story` node
- `Sub-article`: A reference to another `article` node
- `Text`: A text paragraph with optional footnotes

In addition the system has a few **taxonmy terms**:

- `Appeals`: Type of appeal like RRP, 3RP etc.
- `Interactive content type`: Graph, Map etc.
- `Needs and requirements`: "People in Need", "People targeted" and "Requirements" figures
- `Section`: Section an article belongs to, like "Introduction"
- `Story type`: "Story from the field", "Multimedia" etc.

and **media** types:

- `Author`: An author with an image, name and title
- `Image`: An image with a description and credits
- `Video`: A youtube video with credits

Paragraphs are arranged inside an article using the [*layout paragraphs*](https://www.drupal.org/project/layout_paragraphs) module which provides a more intuitive interface than the
default paragraph widget, in addition to its core layout selection feature which is, however, not
used.

Languages
---------

The site is multilingual with English as default language and allowing Arabic,
French and Spanish translations.

`Views` have been created to provide overviews of the publication and
translation status of the different entities (nodes, media, terms) used on the
site with links to create, edit or view those entities in different languages.

Themes
------

The site uses the DSS common design and views and paragraphs for the content.

Theme customizations are in the
[Common design subtheme](html/themes/custom/common_design_subtheme). The site
extensively uses **CSS components**. See the [README](html/themes/custom/common_design_subtheme/README.md)
for more details.


The site also has an administration sub-theme extending the `seven` theme and
providing a few tweaks to the admin interface like full width node forms:
[Common design admin subtheme](html/themes/custom/common_design_admin_subtheme).

Modules
-------

The main contrib modules for this site are the [paragraphs](https://www.drupal.org/project/paragraphs) related ones (see
[composer file](composer.json).

In addition, the site has several custom modules. Most of the modules prefixed
with `gho_` have been inherited from the former GHO site, of which this site
has been forked. The modules prefixed with `ncms_` are specific to the
functioning of this site as a content backend.

- [**GHO Access**](html/modules/custom/gho_access)

  The gho_access module provides granular view permissions for node and media
  entities as well as handling the access to images on public pages, and a
  permission to assign roles. This module also controls the visibility of
  content in a given language based on the `published` and `promoted` status
  of the homepage in that language.

- [**GHO Download**](html/modules/custom/gho_download)

  The gho_download module provides a route to have permanent download links of
  the PDF version of an article (when available).

- [**GHO Fields**](html/modules/custom/gho_fields)

  The gho_fields module provides various field formatters, widgets and templates
  for example to format and translate `needs and requirements` figures, embed
  `datawrapper` graphs etc.

- [**GHO Figures**](html/modules/custom/gho_figures)

  The gho_figures module provides facilities to import `needs and requirements`
  figures from a spreadsheet.

- [**GHO Footnotes**](html/modules/custom/gho_footnotes)

  The gho_footnotes module provides extraction and formatting of footnotes from
  `text` paragraphs and `stories`.

- [**GHO General**](html/modules/custom/gho_general)

  The gho_general module provides tests of the site as well as general
  customizations like "add content" action links for the different content
  admin pages (`/admin/content`), a custom translation manager to keep the admin
  UI in English, blocks for the header and footer and handling of youtube videos
  embeds.

- [**GHO GraphQL**](html/modules/custom/gho_graphql)

  The gho_graphql module provides a GraphQL API that allows external access to
  published content.

- [**GHO Layouts**](html/modules/custom/gho_layouts)

  The gho_layouts module provides addition layouts to use with modules relying
  on the Layout API to arrange display (ex: layout builder module or
  [layout_paragraphs](https://www.drupal.org/project/layout_paragraphs) in the
  case of GHO). This module provides notably a layout plugin to handle
  configurable grids with any number of areas. **Note:** This not really used.

- [**NCMS Publisher**](html/modules/custom/ncms_publisher)

  The ncms_publisher module provides a "publisher" entity that allows the
  definition of external content consumers. Defining a "publisher" allows this
  external service to better integrate its workflow with this site.

- [**NCMS UI**](html/modules/custom/ncms_ui)

  The ncms_ui module provides UI additions that allow this site to function as
  a backend-only site. Full page node views are disabled and replaced with
  modal previews. Login is required for any interaction with the site.

The site has 1 more custom module that could/should be separated from this site
codebase to be independent module that other sites could use:

- [**Linked Responsive Image Media Formatter**](html/modules/custom/linked_responsive_image_media_formatter)

  The linked_responsive_image_media_formatter module, in addition to competing
  for the longest module name, provides a formatter for image media types. This
  formatter can be used to display the image using responsive image styles and
  with extended linking options: link to content, link to media, link to image
  and custom link that can use `tokens`. It also offers the opion to set a
  custom `alt` text using `tokens` as well and an option to display the image
  as background for the link, using the `alt` text as text for the link.

  In the case of this site, this formatter is used to link placeholder images to
  `datawrapper` pages.

Notes
-----

Some notes related to the initial installation and development are available in
the [notes.md](notes.md) file.


Local development
-----------------

HHPC Content Module (CM) uses docksal which is a web-development environment
based on docker.

Install docksal: https://docksal.io/installation

You will need to create a local environment file in the _./.docksal_ folder and
adjust it to match your local requirements.

    touch ./.docksal/docksal-local.env

Refer to _./.docksal/default.docksal-local.env_ for required environment
variables.

Once the above steps are complete, from the project root, run:

    fin init-site

This will install all required packages via composer and setup the local
settings files.

For docksal to run, you will need to stop any other webserver or service on
your system that might bind to port 80.


DATABASE SETUP
--------------

A database has been created automatically as part of the stack setup above.

Pull a database dump from [here](https://snapshots.aws.ahconu.org/hpc-content) to get a
fresh copy and seed your local database.


COMPOSER
--------

Using docksal, you can run any composer command as **_fin composer {COMMAND}_**.


DRUSH
-----

Drush commands can be run as **_fin drush {COMMAND}_**. Eg:

    fin drush cr


Local testing
-------------

**With Docksal**

Note: Replace `test.hpc-content-site.docksal.site` below with the appriate hostname for
your local site (ex: `hpc-content-site.test`).

```bash
mkdir -p ./html/sites/test
cp ./.travis/local/* ./html/sites/test/

fin db create test
fin drush --uri=test.hpc-content-site.docksal.site si minimal -y
fin drush --uri=test.hpc-content-site.docksal.site cset system.site uuid $(grep uuid ./config/system.site.yml | awk '{print $2}') -y
fin drush --uri=test.hpc-content-site.docksal.site cim -y
fin drush --uri=test.hpc-content-site.docksal.site cr

fin drush --uri=test.hpc-content-site.docksal.site en yaml_content -y
fin drush --uri=test.hpc-content-site.docksal.site yaml-content-import /var/www/.travis/
```

Run tests using docksal

```bash
fin exec DTT_BASE_URL=http://test.hpc-content-site.docksal.site/ ./vendor/bin/phpunit --debug --colors --testsuite=existing-site,existing-site-javascript --printer '\Drupal\Tests\Listeners\HtmlOutputPrinter'
```
