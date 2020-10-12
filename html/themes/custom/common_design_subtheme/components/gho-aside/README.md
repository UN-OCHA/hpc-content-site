Global Humanitarian Overview - Aside Component
==============================================

Styling for "aside" sections. Ex: Facts and Figures, Field stories, Interactive
graphics, Multimedia etc.

To use that component, attach the library in a twig template for example with
`{{ attach_library('common_design_subtheme/gho-bleed') }}` and add the
`gho-aside` class.

Add the `gho-aside--dark` class to set a black background and change the font
color to white.

Add the `gho-aside__title` to any title field for the aside (ex: title field
for Facts and Figures or node title for Field stories).

To display a pre-title add a `data-pre-title` attribute with the translable
pre-title to the title element.
