<?php

namespace Codewiser\Inarticulate;

use Illuminate\Support\Collection;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $redisExpire = 0;

    public function getRedisExpire()
    {
        return $this->redisExpire;
    }

    public function setRedisExpire( $redisExpire)
    {
        $this->redisExpire = $redisExpire;

        return $this;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  Builder  $query
     * @return Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Create a new Collection instance.
     *
     * @param  array  $models
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }
}