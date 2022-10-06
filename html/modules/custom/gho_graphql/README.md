HPC Content Module - GraphQL Module
===============================================

The gho_graphql module provides a GraphQL API that allows external access to
published content.

Examples
--------

## Example query for a specific article ##

{
  article(id: 1) {
    title
    caption {
      title
      body
    }
    heroImage {
      credits
      image {
        url
        width
        height
      }
    }
    content {
      id
      type
      typeLabel
      rendered
    }
  }
}

## Example query to search articles by title ##

{
  articleSearch(title:"Global") {
    count
    items {
      id
      title
    }
  }
}

## Example query for a specific paragraph ##

{
  paragraph(id: 1) {
    id
    type
    typeLabel
    rendered
  }
}
