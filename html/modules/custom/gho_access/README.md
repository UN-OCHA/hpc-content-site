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
