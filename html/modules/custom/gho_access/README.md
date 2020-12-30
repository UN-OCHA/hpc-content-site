Global Humanitarian Overview - Access module
============================================

This module defines various permissions.

Content
-------

This module defines permissions to **view** content (nodes) per bundle.

It denies **view** access to node pages for users without the corresponding
`Node Bundle: view published content` permission.

To work, the `view published content` must be checked for anonymous and
authenticated users (otherwise they are already denied access).

This modules only deals with **published** content. Access to unpublished
content is managed by the `view unpubished content` permission independently
of this module.

Media
-----

This module defines permissions to **view** media per bundle.

It denies **view** access to media for users without the corresponding
`Media Type: view published media` permission.

To work, the `view media` must be checked for authenticated users (otherwise
they are already denied access).

This modules only deals with **published** media. Access to unpublished
media is managed by the `view own unpubished media` permission independently
of this module.

Roles
-----

This module also provides a permission to assign user roles, decoupling it
from the `administer permissions` permission.

Language
--------

This module ensures that content in a language is only displayed when the
homepage in that language is published and "promoted". It also ensures that
there is no mix of content in different languages due to Drupal's behavior to
show content in the default language when no translation is available.
People with the `view untranslated content` permission can always see any
content even if it doesn't match the current language.

Menu tree manipulator
---------------------

This module overrides the menu tree manipulator mostly to ensure the
`nodeAccessCheck` manipulation respects the global language visibility as
described above.
