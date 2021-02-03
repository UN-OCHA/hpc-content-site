OCHA Common Design sub theme for the Drupal 8 Global Humanitarian Overview site
===============================================================================

See below for [generic information](#ocha-common-design-sub-theme-for-drupal-8)
about the OCHA Common Design sub theme.

Requirements
------------

The customizations for the GHO site require the installation of the
[components](https://www.drupal.org/project/components) drupal module.

Issues
------

The heading hierarchy is "incorrect" because the user menu at the top has a `<h2>`
title while the `<h1>` appears later in the page.

For GHO, currently the `<h1>` is the node or page title (not the site name in
the header), which works fine because the homepage is a node with its own title
corresponding to the site name. This is more "problematic" for other sites like
https://reliefweb.int where the homepage is a series of sections and having the
site logo/name as `<h1>` makes more sense. There is an open discussion about that
[here](https://humanitarian.atlassian.net/browse/CD-208), though the problem
with the heading in the user menu is still an issue in terms of hierarchy.

Reference: https://www.w3.org/WAI/tutorials/page-structure/headings/

Notes
-----

**Site name**

Ensure `site_name` is selected in `/admin/structure/block/manage/sitebranding`
so that it's available in the `system-branding` block.

**Page title block**

As described in https://www.drupal.org/project/drupal/issues/2887071, using
the visibility options on the page title for example to hide it on some node
pages will cause the page title on views pages and maybe other places to be
hidden as well... So instead we remove the page title block in a
hook_preproprecess_page() if it was already rendered otherwise.

Customizations
--------------

The list below contains additions to the default common design subtheme:

### Base styling

**TODO**: add more info about the CD files that were changed to override rules
for the header and footer.

- [CD header](sass/cd/cd-header/_cd-header.scss)

  Added `position: relative;` to `.cd-header` to fix position of the main menu
  dropdown. This could/should be added to the `common_design` theme.

- [CD layout](sass/cd/cd-layout/_cd-layout.scss)

  Changed the `flex-basis` and `flex-grow` of the `.cd-layout-content` to
  ensure content spans the entire width of the main content area.

- [CD variables](sass/cd/_cd-variables.scss)

  Various changes to colors. Definition of the `--reading-width` and
  `--content-width` variables used to restrict the width of the content like
  texts, stories etc.

- [Editorial](sass/components/_editorial.scss)

  Editorial flags to show unpublished/untranslated entities.

- [Forms](sass/components/_forms.scss)

  Styling for the drupal inline forms.

- [Page title](sass/components/_page_title.scss)

  Styling for the drupal page title.

### Components

- [components/gho-achievement](components/gho-achievement):

  Styling for the `achievement` nodes and paragraphs in articles. This includes
  notably the display of an icon next to the achievement title using the
  Humanitarian Icons set.

- [components/gho-appeal-tags](components/gho-appeal-tags):

  Styling for the `appeal tags` displayed next to the title of appeal articles.
  They are like tiny vignettes attached to the title with a 3-4 letters tags
  like `3RP`. Each tag has its own background color.

- [components/gho-article](components/gho-article):

  Styling for the main `articles` (not sub-articles), mostly for the header that
  includes hero image, pre-title and title.

- [components/gho-article-list](components/gho-article-list):

  Styling for the `article lists` displayed in `Section index` paragraphs on the
  home page. Those are simple list of links to articles with an optional title.

- [components/gho-aside](components/gho-aside):

  Base styling for other components: `stories`, `interactive content` and
  `photo galleries`. This includes pre-title, title and background.

- [components/gho-author](components/gho-author):

  Styling for the `authorship` section (author's image, name and title). This is
  currently only used on the Foreword page by the USG.

- [components/gho-bottom-figure-row](components/gho-bottom-figure-row):

  Styling for the `"bottom" figures` which are free form figures with a label and
  a value. They appear mostly below the `needs and requirements` figures.

- [components/gho-caption](components/gho-caption):

  Base styling for the caption texts on `stories`, `photo galleries` and below
  `hero images`.

- [components/gho-embed](components/gho-embed):

  Styling to make embedded content (youtube videos) responsive.

- [components/gho-facts-and-figures](components/gho-facts-and-figures):

  Styling for the `facts and figures` paragraphs in articles. This uses the
  `gho-aside` for the base styling and then styles the image + text in a 3
  columns manner on desktop.

- [components/gho-footnotes](components/gho-footnotes):

  Styling for the footnotes that can accompany `text` paragraphs or `stories`.
  This component includes a **js** script to display the footnotes at the
  bottom of the screen when thare are references visibile in the upper half.

- [components/gho-further-reading](components/gho-further-reading):

  Styling for the `further reading` paragraphs that contain a list of external
  links with their source.

- [components/gho-hero-image](components/gho-hero-image):

  Styling for the "scroll down" icon/message on top of the homepage hero image.

- [components/gho-home-page](components/gho-home-page):

  Styling for the home page particularities (center alignment, large GHO logo
  etc.).

- [components/gho-interactive-content](components/gho-interactive-content):

  Styling for the `interactive content` paragraphs (datawrapper embeds or
  placeholder images).

- [components/gho-needs-and-requirements](components/gho-needs-and-requirements):

  Styling for the `needs and requirements` figures.

- [components/gho-page-404](components/gho-page-404):

  Styling for the 404 Not Found page which can contain notably the main
  navigation links.

- [components/gho-page-node](components/gho-page-node):

  Styling for the node pages. It handles "bleeding" by removing the max-width
  and padding of the `<main>` so that by default content spans the entire width
  of the `<body>` and instead provides styling for a `content-width` class that
  can be added to components or part of components that need to be contained in
  a specific width (see `--content-width` css variables in the
  [cd-variables](sass/cs/_cd-variable.scss) sass file).

- [components/gho-photo-gallery](components/gho-photo-gallery):

  Styling for the `photo gallery` paragraphs that can have images in displayed
  in 1 column or 2 columns depeneding on the number of images in addition to
  a short caption text.

- [components/gho-related-articles](components/gho-related-articles):

  Styling for the `next article` displayed at the bottom of articles with a
  an image, title and summary.

- [components/gho-section-index](components/gho-section-index):

  Styling for the `section index` paragraphs on the homepage that have a
  "hero image", caption, description and some `article lists`.

- [components/gho-separator](components/gho-separator):

  Styling for the `separator` paragraphs which are displayed as a simple line
  using the full `reading-width` or half of it (see the `--reading-width` in the
  [cd-variables](sass/cs/_cd-variable.scss) sass file).

- [components/gho-signature](components/gho-signature):

  Styling for the USG signature.

- [components/gho-social-links](components/gho-social-links):

  Styling for the sharing icons displayed on articles and stories.

- [components/gho-story](components/gho-story):

  Styling for the `story` nodes in "full" mode (individual page) or "teaser"
  mode (when included in an article).

- [components/gho-sub-article](components/gho-sub-article):

  Styling for `articles` that appear inside another `article`. This mostly
  focuses on the header with or without a hero image.

- [components/gho-text](components/gho-text):

  Styling for the `text` paragraphs, notably to limit their width to the
  `reading-width` (see the `--reading-width` in the
  [cd-variables](sass/cs/_cd-variable.scss) sass file).

### Layouts

Note: those are not used on GHO.

- [layouts/twocol_section](layouts/twocol_section):

  Overrides the layout builder two columns section to add margins and use the
  common_design breakpoints.

- [layouts/threecol_section](layouts/threecol_section):

  Overrides the layout builder three columns section to add margins and use the
  common_design breakpoints.

- [layouts/fourcol_section](layouts/fourcol_section):

  Overrides the layout builder four columns section to add margins and use the
  common_design breakpoints.

### Templates

The GHO sites uses a lots of template overrides to use the css components
described above and to enable customized styling of elements from the Common
Design.

#### Common design overrides

- [Site logo block (system branding)](templates/block/block--system-branding-block.html.twig)

  This block is for the site logo with the link to the homepage. The overrides
  removes the wrapping `h1`.
  In the case of GHO, the homepage is a node with the site title so we
  don't need to have a `h1` there. Other non-node pages use the `page-title`
  block which uses a `h1` tag as well. So that should be fairly consistent.

- [Site main navigation - block](templates/block/block--gho-main-menu.html.twig):

  GHO uses a custom block defined in the `gho_general` custom module for the
  main navigation shown in the header to enable node access check. Though the
  name of the template is different, it's basically a copy of the
  `block--system-menu-block--main.html.twig` from the main theme.

- [Site main navigation - menu](templates/navigation/menu--main.html.twig):

  The template for the main navigation menu is also overridden to only display
  top items when they have children (article links) and also to handle the
  the "download report" link.

- [Header](templates/header):

  Various overrides to enable translations and RTL support.

- [Footer](templates/footer):

  Various overrides to enable translations and RTL support.

- [Soft footer](templates/blocks/):

  Not an override per se, but there are templates for the blocks used in the
  soft footer (that differs from the soft footer as defined in the base common
  design theme).

#### HTML/page overrides

- [HTML](templates/html.html.twig):

  The `html` template is overridden to load the Arabic font as soon as possible
  via an `<style>` instead of a linked stylesheet.

- [Page](templates/page.html.twig):

  The `page` template is overridden to include the header and footer from the
  `common_design_subtheme` instead of the base theme ones.

#### Content

- [Nodes](templates/nodes):

  There are templates for the `article`, `story` and `achievement` nodes in
  various **view modes** to use the css components described above and often
  to display the node header elements like the "hero image", "pre title",
  "title" etc. outside of the `content`.

- [Paragraphs](templates/paragraphs):

  There are templates for each `paragraph type` to use the css components
  described above.

- [Fields](templates/fields):

  Many fields have overridden templates to simplify the markup and add relevant
  classes to use with the css components.

- [Media](templates/media):

  The base media template is overridden to simplify the markum. There is also
  a template for the `author` media to help with the styling of the author
  image.

- [Taxonomy terms](templates/taxonomy):

  There are templates for the `appeal tags` and `needs and reuirements` figures
  to use the corresponding css components.

### Preprocessors

- The [common_design_subtheme.theme](common_design_subtheme.theme) file contains
  several preprocess hooks to work with the new components and page styling.

Translations
------------

The following templates are overridden to allow for translation of the texts.

**TODO:** Once validated, the changes could/should be pushed upstream in the
`common_design` theme.

- [cd-footer/cd-copyright.html.twig](templates/cd/cd-footer/cd-copyright.html.twig)
- [cd-footer/cd-mandate.html.twig](templates/cd/cd-footer/cd-mandate.html.twig)
- [cd-header/cd-ocha.html.twig](templates/cd/cd-header/cd-ocha.html.twig)

---

## OCHA Common Design sub theme for Drupal 8

A sub theme, extending [common_design](https://github.com/UN-OCHA/common_design) base theme.

This can be used as a starting point for implementations. Add components, override and extend base theme as needed. Refer to [Drupal 8 Theming documentation](https://www.drupal.org/docs/8/theming) for more.

Clone this repo to /themes/custom/ and rename the folder and associated theme files from
`common_design_subtheme` to your theme name.

### Customise the logo

- Set the logo `logo: 'img/logos/logo.svg'` in the `common_design_subtheme.info.yml` file, and in the `sass/cd-header/_cd-logo.scss` partial override file.
- Adjust the grid column width in `sass/cd-header/_cd-header.scss` partial override file to accommodate the logo.

### Change the path of the libraries

In the `common_design_subtheme.info.yml` change the path of the global style sheet to reflect the new sub theme name.

```
libraries:
- common_design_subtheme/global-styling
```

### Customise the favicon and homescreen icons

Replace the favicon in the theme's root, and the homescreen icons in `img/` with branded versions

### Customise colours

- Change colour-related variable names and values in `sass/cd/_cd_variables.scss` and replace in all references to in partial overrides in `common_design_subtheme/sass/cd/`

### Other customisations

Override sass partials and extend twig templates from the base theme as needed, copying them into the sub theme and linking them using `@import` for sass and `extend` or `embed` for twig templates.

Add new components by defining new libraries in `common_design_subtheme.libraries.yml` and attaching them to relevant templates. Or use existing components from `common_design.libraries.yml` base theme by attaching the libraries to twig template overrides in the sub theme.

Override theme preprocess functions by copying from common_design.theme and editing as needed. For example, if new icons are added, a new icon sprite will need to be generated and the `common_design_preprocess_html` hook used to attach the icon sprite to the page will need a new path to reflect the sub theme's icon sprite location.

Refer to [common_design README](https://github.com/UN-OCHA/common_design/#ocha-common-design-base-theme-for-drupal-8) for general details about base theme and instructions for compilation. There should be no need to compile the base theme, only the sub theme.
