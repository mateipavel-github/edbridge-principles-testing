<?php

namespace App\Models;

use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Pagination\Paginator;

class Job
{
    use ForwardsCalls;

    public $id;
    public $queue;
    public $status;
    public $created_at;
    public $payload;
    protected $table = 'jobs';
    protected $primaryKey = 'id';

    public function __construct($attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function query()
    {
        return new static;
    }

    public function newQuery()
    {
        return $this;
    }

    public function get()
    {
        return collect();
    }

    public function tap($callback)
    {
        $callback($this);
        return $this;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getKey()
    {
        return $this->id;
    }

    public function getQuery()
    {
        return $this;
    }

    public function getModel()
    {
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function orderBy($column, $direction = 'asc')
    {
        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        return $this;
    }

    public function latest($column = 'created_at')
    {
        return $this;
    }

    public function take($value)
    {
        return $this;
    }

    public function limit($value)
    {
        return $this;
    }

    public function offset($value)
    {
        return $this;
    }

    public function getQualifiedKeyName()
    {
        return $this->getTable() . '.' . $this->getKeyName();
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function usesTimestamps()
    {
        return false;
    }

    public function with($relations)
    {
        return $this;
    }

    public function withCount($relations)
    {
        return $this;
    }

    public function load($relations)
    {
        return $this;
    }

    public function loadCount($relations)
    {
        return $this;
    }

    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return new Paginator([], $perPage);
    }

    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return new Paginator([], $perPage);
    }

    public function forPage($page, $perPage = 15)
    {
        return $this;
    }
} 