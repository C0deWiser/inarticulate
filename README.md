# Inarticulate

Inarticulate is a Eloquent extender to work with Redis storage.

Inarticulate Model is absolutely the same as Eloquent Model. It has one important difference — there are no autoincrement keys in Redis.
So Inarticulate has

```php
public $incrementing = false;
```

Inarticulate Builder extends Eloquent Builder, but it overrides just few methods — find, insert, update, delete and exists.
So just do not call any others — that are not applicable for Redis. 

As Inarticulate extends Eloquent Model your may use attributes Accessors and Mutators, castings, property guards etc.

## Redis key

Redis key is build up from three properties — hashed APP_KEY, `$table` property and model primary key. Redis key will look like

```
d781b523ed68d90c599832956ff10ea9:article:243
``` 

## Redis expire

Set positive `protected $redisExpire` value to set expiration timout for Model.

## Usage

To prevent Builder from resolving undefined attributes as a relations (that is not supported, you know) just define default attribute values in `$attributes` array. 

```php
/**
 * @property mixed id
 * @property string title
 * @property string body
 */
class Article extneds \Codewiser\Inarticulate\Model
{
    protected $attributes = [
        'title' => null,
        'body' => null
    ];
}
```

```php

$article = new Article();

$article->exists; // FALSE

$article->id = 1;
$article->title = "Title";
$article->save();

$article->exists; // TRUE

```

```php
$article = Article::query()->findOrFail(1);

echo $article->title; 

```

## Builder methods

Fearlessly use these Builder methods:

* find
* findOrFail
* findOrNew
* findMany
* exists

Get all records
```php
Model::all();
// or
Model::query()->get();
```

