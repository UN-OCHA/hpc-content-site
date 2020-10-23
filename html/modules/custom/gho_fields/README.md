Global Humanitarian Overview - Fields Module
============================================

This module provides some field formatters, widgets and templates.

Dataset links
-------------

- [Formatter](src/Plugin/Field/FieldFormatter/GhoDatasetLinkFormatter.php)

  Uses the [gho-further-reading-link-formatter.html.twig](templates/gho-dataset-link-formatter.html.twig) template with the `url` and `source` (repurposed link title) variables.

- [Widget](src/Plugin/Field/FieldWidget/GhoDatasetLinkWidget.php)

  Simply renames the `title` field to "Source".


Further reading links
---------------------

- [Formatter](src/Plugin/Field/FieldFormatter/GhoFurtherReadingLinkFormatter.php)

  Uses the [gho-further-reading-link-formatter.html.twig](templates/gho-further-reading-link-formatter.html.twig) template with the `title`, `url`
  and `source` variables.

- [Widget](src/Plugin/Field/FieldWidget/GhoFurtherReadingLinkWidget.php)

  Add a mandatory `source` field in addition to the `uri` and `title`.
