<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Class AuthorObserver
 * @package ShibuyaKosuke\LaravelCrudCommand\Observers
 */
class AuthorObserver
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * AuthorObserver constructor.
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth::user();
    }

    /**
     * get auth user id
     * @return int|null
     */
    private function authId(): ?int
    {
        if ($this->auth) {
            return $this->auth->id;
        }
        return \config('make_crud.author_observer_default_user_id');
    }

    /**
     * @param Model $model
     */
    public function creating(Model $model): void
    {
        $model->setAttribute(config('make_crud.columns.author.created_by'), $this->authId());
    }

    /**
     * @param Model $model
     */
    public function updating(Model $model): void
    {
        $model->setAttribute(config('make_crud.columns.author.updated_by'), $this->authId());
    }

    /**
     * @param Model $model
     */
    public function saving(Model $model): void
    {
        $this->clearCache($model);
        $model->setAttribute(config('make_crud.columns.author.updated_by'), $this->authId());
    }

    /**
     * @param Model $model
     */
    public function deleting(Model $model): void
    {
        $model->setAttribute(config('make_crud.columns.author.deleted_by'), $this->authId());
    }

    /**
     * @param Model $model
     */
    public function restoring(Model $model): void
    {
        $model->setAttribute(config('make_crud.columns.author.restored_by'), $this->authId());
    }

    /**
     * @param Model $model
     */
    private function clearCache(Model $model): void
    {
        $model::flushCache();
    }
}
