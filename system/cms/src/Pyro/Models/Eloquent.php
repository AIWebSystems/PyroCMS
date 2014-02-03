<?php namespace Pyro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Events\Dispatcher;
use Pyro\Support\Contracts\ArrayableInterface;
use Pyro\Support\PresenterDecorator;

/**
 * Eloquent Model
 *
 * Extends Illuminates Eloquent model and adds validation
 *
 * @author      PyroCMS Dev Team
 * @package     PyroCMS\Core\Models\Eloquent
 */
abstract class Eloquent extends Model implements ArrayableInterface
{   
    /**
     * Cache minutes
     * 
     * @var integer|boolean
     */ 
    protected $cacheMinutes = false;

    /**
     * Skio validation
     * @var boolean
     */
    public $skip_validation = false;

    /**
     * Replicated
     * @var boolean
     */
    protected $replicated = false;

    /**
     * Collection class
     * @var string
     */
    protected $collectionClass = 'Pyro\Models\EloquentCollection';

    /**
     * Presenter class
     */ 
    protected $presenterClass = 'Pyro\Support\Presenter';

    /**
     * Boot
     * 
     * @return void
     */ 
    public static function boot()
    {
        parent::boot();

        static::$dispatcher = new Dispatcher;
    }

    /**
     * Get cache minutes
     * @return integer
     */
    public function getCacheMinutes()
    {
        return $this->cacheMinutes;
    }

    /**
     * Set cache minutes
     * @return integer
     */
    public function setCacheMinutes($cacheMinutes)
    {
        $this->cacheMinutes = $cacheMinutes;

        return $this;
    }

    /**
     * Get attribute keys
     * 
     * @return array
     */ 
    public function getAttributeKeys()
    {
        return array_keys($this->getAttributes());
    }

    /**
     * Update
     * 
     * @param array $attributes
     * @return Pyro\Models\Eloquent|boolean
     */ 
    public function update(array $attributes = array())
    {
        // Remove any post values that do not correspond to existing columns
        foreach ($attributes as $key => $value)
        {
            if ( ! in_array($key, $this->getAttributeKeys()))
            {
                unset($attributes[$key]);
            }
        }

        return parent::update($attributes);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        if (method_exists($this, 'validate') and ! $this->validate() and ! $this->skip_validation)
        {
            return false;
        }

        $this->flushCacheCollection();

        return parent::save($options);
    }

    /**
     * Delete
     * 
     * @return boolean
     */ 
    public function delete()
    {
        $this->flushCacheCollection();

        return parent::delete();
    }

    /**
     * Replicate 
     * 
     * @return object The model clone
     */
    public function replicate()
    {
        $clone = parent::replicate();
        $clone->skip_validation = true;
        $clone->replicated = true;
        return $clone;
    }

    /**
     * Set presenter class
     * 
     * @return Pyro\Models\Eloquent
     */ 
    public function setPresenterClass($presenterClass = null)
    {
        $this->presenterClass = $presenterClass;

        return $this;
    }

    /**
     * Get presenter
     * 
     * @Return Pyro\Support\Presenter|Pyro\Models\Eloquent
     */ 
    public function getPresenter()
    {
        $decorator = new PresenterDecorator;

        return $decorator->decorate($this);
    }

    /**
     * New collection
     * @param  array  $models The array of models
     * @return object         The Collection object
     */
    public function newCollection(array $models = array())
    {
        $collection = $this->collectionClass;

        return new $collection($models, $this);
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @param  bool  $excludeDeleted
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = new EloquentQueryBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        $builder->setModel($this)->with($this->with);

        if ($excludeDeleted and $this->softDelete)
        {
                $builder->whereNull($this->getQualifiedDeletedAtColumn());
        }

        return $builder;
    }

    /**
     * Flush cache collection
     * 
     * @return Pyro\Models\Eloquent
     */ 
    public function flushCacheCollection()
    {
        ci()->cache->collection($this->getCacheCollectionKey())->flush();

        return $this;
    }

    /**
     * Get cache collection key
     * @return string
     */
    public function getCacheCollectionKey($suffix = null)
    {
        return $this->getCacheCollectionPrefix().$suffix;
    }

    /**
     * Get cache collection prefix
     * @return string
     */
    public function getCacheCollectionPrefix()
    {
        return get_called_class();
    }

    /**
     * Get relation
     * 
     * @return Pyro\Models\Eloquent|Pyro\Models\EloquentCollection|null
     */ 
    public function getRelation($attribute)
    {
        if (isset($this->relations[$attribute])) return $this->relations[$attribute];

        return null;
    }

    public function hasRelationMethod($attribute)
    {
        if ( ! method_exists($this, $attribute)) return false;

        $relation = $this->{$attribute}();

        return ($relation instanceof Relation);
    }
}

/* End of file Eloquent.php */