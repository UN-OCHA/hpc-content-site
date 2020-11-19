Global Humanitarian Overview - Page Node Component
==================================================

This is not a component per se but is used a dependency to the article and
homepage. It provides the basic rules to handle the use of the full width for
hero images, top border, black backround for stories etc.

This done by removing the max-width and left/right paddings of the `<main>`
element on node pages and use a `content-width` class on the elements that don't
use the full width.

If a component needs to have some of its children use the full width, then put
the `content-width` class on the elements that need to be constrained otherwise
put the `content-width` on the top element of the component.
