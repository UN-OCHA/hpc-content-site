HPC Content Module - GraphQL Module
===============================================

The ncms_graphql module provides a GraphQL API that allows external access to
published content.

Examples
--------

## Example query to search documents by title ##

{
  documentSearch(title:"Afgha") {
    count
    items {
      id
      title
    }
  }
}

## Example query for a specific document ##

{
  document(id: 1) {
    title
    summary
    tags
    image {
      credits
      imageUrl
    }
    chapters {
      id
      title
      summary
      articles {
        id
      }
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

## Example query for a specific article ##

{
  article(id: 1) {
    title
    tags
    image {
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

## Example query for a specific paragraph ##

{
  paragraph(id: 1) {
    id
    type
    typeLabel
    rendered
  }
}
