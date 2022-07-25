Patches
=======

Patches for the Drupal core, libraries, modules and themes.


Drupal core patches
-------------------

###  Core - core/lib/Drupal/Component/Utility/Xss.php

- `core--drupal--xss-prevent-protocol-stripping-on-datetime-attribute.patch`

  https://www.drupal.org/node/2544110
  Prevent protocol stripping on the [datetime](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/time)
  attribute as it truncates dates like `2020-01-01T:00:00:00` to `00`...


Drupal module patches
---------------------

###  layout_paragraphs

- `module--layout-paragraphs--display-proper-language-in-preview.patch`

  This ensures the proper version of the entity is displayed in the preview
  when translating: paragraph in the target language if availalble or in the
  source language otherwise.
  Related issue: https://www.drupal.org/project/layout_paragraphs/issues/3182031
  TODO: create new issue on the layout_paragraphs project.
