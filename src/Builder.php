<?php


namespace Codewiser\Inarticulate;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class Builder extends \Illuminate\Database\Eloquent\Builder
{

    /**
     * The model being queried.
     *
     * @var Model
     */
    protected $model;

    /**
     * Get Redis key for this Model.
     *
     * @return string
     */
    public function getRedisKey($id)
    {
        return md5(env('APP_KEY')) . ':' . $this->model->getTable() . ':' . $id;
    }


    protected function serializeToRedis($attributes)
    {
        return json_encode($attributes);
    }

    protected function wakeUpFromRedis($payload)
    {
        return json_decode($payload, true);
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param  array  $attributes
     * @return Model|static
     */
    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes)->setRedisExpire(
            $this->model->getRedisExpire()
        );
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return true;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.

        foreach ($values as $item) {
            $id = $item[$this->model->getKeyName()];
            $key = $this->getRedisKey($id);

            if ($ttl = $this->model->getRedisExpire()) {
                Redis::setex($key, $ttl, $this->serializeToRedis($item));
            } else {
                Redis::set($key, $this->serializeToRedis($item));
            }
        }

        return true;
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $key = $this->getRedisKey($this->model->getKey());

        if ($ttl = $this->model->getRedisExpire()) {
            return Redis::setex($key, $ttl, $this->serializeToRedis($values));
        } else {
            return Redis::set($key, $this->serializeToRedis($values));
        }
    }

    /**
     * Delete a record from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        if (isset($this->onDelete)) {
            return call_user_func($this->onDelete, $this);
        }

        $key = $this->getRedisKey($this->model->getKey());

        return Redis::del($key);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
     */
    public function find($id, $columns = ['*'])
    {
        $item = Redis::get($this->getRedisKey($id));

        return $item ? $this->hydrate([$this->wakeUpFromRedis($item)])->first() : null;
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        $key = $this->getRedisKey($this->model->getKey());

        return (boolean)Redis::get($key);
    }
}