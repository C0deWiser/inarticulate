<?php

namespace Codewiser\Inarticulate;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class Model
 * @package Codewiser\Inarticulate
 *
 * @method static static find($id)
 * @method static static findOrFail($id)
 * @method static static findOrNew($id)
 * @method static Collection|static[] findMany($ids)
 */
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
     * Perform a model update operation.
     *
     * @param \Illuminate\Database\Eloquent\Builder|Builder $query
     * @return bool
     */
    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            // Redis can not emerge attributes, use full update
            $this->setKeysForSaveQuery($query)->update($this->getAttributes());

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Reload a fresh model instance from the database.
     *
     * @param  array|string  $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (! $this->exists) {
            return null;
        }

        return static::query()->find($this->getKey());
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