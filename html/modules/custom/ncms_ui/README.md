HPC Content Module - NCMS UI Module
=============================================

The ncms_ui module provides UI additions that allow this site to function as a
backend-only site. Full page node views are disabled and replaced with modal
previews. Login is required for any interaction with the site.


Content space bases access
--------------------------

Edit access to nodes having a "Content space" field (single-value
'field_content_space') is granted if a user is assigned to the same content
space (via 'field_content_space' on the account). When creating new content for
a node type that has a "Content space" field, the options are limited to the
content spaces available to the user.
