# Fundamentals plugin

This plugin contains some fundamental functionalities that facilitate application development.

## Common translations

Fundamentals plugin includes some common language translations which can be used elsewhere.

## Publishable

Publishable trait can be used on models that allow content to be published or hidden from the website.
Model entity must use the Publishable trait and the table must include field is_published.

#### Usage in model
    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Publishable;
        
        ...
    }

## Relationable

Relationable trait enables `repeater` to be used as relations via Relation behavior.

#### Usage in model
    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Relationable;
        
        ...
    }
