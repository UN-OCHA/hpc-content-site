HPC Content Module - Fields Module
============================================

This module provides some field formatters, widgets and templates as well as
**views** field plugins.

Formatters and widgets
----------------------

### Article List

- [Formatter](src/Plugin/Field/FieldFormatter/GhoArticleListFormatter.php)

  Handles the list of links displayed in the `section index` paragraphs in the
  homepage, ensuring only node access is respected.


### Compact Numbers

- [Formatter](src/Plugin/Field/FieldFormatter/GhoNumberFormatter.php)

  Handles the language aware display of compact numbers (ex: 1.2 million).

### Caption

- [Formatter](src/Plugin/Field/FieldFormatter/GhoCaptionFormatter.php)

  Simple formatter for `caption` "double fields" (location + text) used for
  example for the caption text of hero image of articles.
  See https://www.drupal.org/project/double_field

### Dataset links

- [Formatter](src/Plugin/Field/FieldFormatter/GhoDatasetLinkFormatter.php)

  Uses the [gho-further-reading-link-formatter.html.twig](templates/gho-dataset-link-formatter.html.twig) template with the `url` and `source` (repurposed link title) variables.

- [Widget](src/Plugin/Field/FieldWidget/GhoDatasetLinkWidget.php)

  Simply renames the `title` field to "Source".

### Interactive content

- [Formatter](src/Plugin/Field/FieldFormatter/GhoInteractiveContentFormatter.php)

  Uses the [gho-interactive-content-formatter.html.twig](templates/gho-interactive-content-formatter.html.twig) template with the extracted and validated attributes from the embed iframe
  snippet (url, width, title, aria-label etc.).

- [Widget](src/Plugin/Field/FieldWidget/GhoInteractiveContentWidget.php)

  Extends the textarea widget with validation and sanitation of embed
  iframe snippets.

### Figures

- [Formatter](src/Plugin/Field/FieldFormatter/GhoFiguresFormatter.php)

  Formatter to select how to display figures (label + text): either `small`
  like the "botton figures row" or `large` like the "needs and requirements".

### Further reading links

- [Formatter](src/Plugin/Field/FieldFormatter/GhoFurtherReadingLinkFormatter.php)

  Uses the [gho-further-reading-link-formatter.html.twig](templates/gho-further-reading-link-formatter.html.twig) template with the `title`, `url`
  and `source` variables.

- [Widget](src/Plugin/Field/FieldWidget/GhoFurtherReadingLinkWidget.php)

  Add a mandatory `source` field in addition to the `uri` and `title`.

### Related Articles

**Note:** This is not used anymore as the homepage uses `article lists` now.

- [Formatter](src/Plugin/Field/FieldFormatter/GhoRelatedArticlesFormatter.php)

  Uses the [gho-related-articles-formatter.html.twig](templates/gho-related-articles-formatter.html.twig) template with the `title` and `list`
  (of related articles) variables.

- [Widget](src/Plugin/Field/FieldWidget/GhoMenuSelectWidget.php)

  Allow the selection of a direct child of the `main` navigation menu.

Views
-----

### Translation links

This module provides a [translation links](src/Plugin/views/field/GhoTranslationLinks.php)
views field plugin that provides links to create, edit or view entities in the
different languages enabled on the site.
