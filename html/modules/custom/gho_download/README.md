HPC Content Module - Download module
==============================================

This module provides a route to have permanent download URLs for the PDF version
of an article: https://gho.unocha.org/node/NODEID/download.

Drupal being what it is, the original filename is not saved... so unfortunately
the filename when saving the above url may have the `_X` suffix. [0]

It's also not possible to have drupal rename the file to preserve its URL. [1]

- [0] https://www.drupal.org/project/drupal/issues/3032376
- [1] https://www.drupal.org/project/drupal/issues/2648816

This is inspired from https://www.drupal.org/project/media_entity_download
