Global Humanitarian Overview - Footnotes module
===============================================

This module processes text paragraph footnotes.

The current approach is to display the footnotes as close as possible to the
paragraph that has references to them. This is similar in concept to the print
version where footnotes are at the bottom of the page with references to them as
opposed to have them grouped at the end of the document.

This attempts to provide accessible markup as much as possible while also
ensuring translations can work. Further improvements can be made with CSS and
javascript if necessary.

This assumes there is a `text` paragraph type with a `field_text` field and a
`field_footnotes` field. So this works in conjunction with the footnotes styles
for the ckeditor present in the `common_design_subtheme`.

**Notes**

- The footnotes processing is in a separate module and not in the
  `common_design_subtheme` so that the processing is also done for text paragraphs
  in the edit forms.
- The processing could be altered to group all the footnotes together and add
  them a the bottom of the page at least when rendering a full article vis some
  kind of accumulator and index incrementor. That would probably not work well
  with the edit forms but it's probably better to display the footnotes with
  their associated text in the forms anyway.
