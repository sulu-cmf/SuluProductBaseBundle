# Development

This documentation contains information about product-bundle development.

## Constants

### Type

Types are loaded into the database and the container with data-fixtures.
They are accessible with the following parameter: `sulu_product.product_types_map`

This parameter contains a key to id mapping:

e.g.

```
[
  PRODUCT_VARIANT => 5,
]
```

### Status

Statuses are loaded into the database with data-fixtures.
All available statuses are defined in the Status entity as constants.
