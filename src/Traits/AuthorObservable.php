<?php

namespace ShibuyaKosuke\LaravelCrudCommand\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ShibuyaKosuke\LaravelCrudCommand\Observers\AuthorObserver;

trait AuthorObservable
{
    public static function bootAuthorObservable()
    {
        self::observe(AuthorObserver::class);
    }

    /**
     * author created by
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, config('make_crud.columns.author.created_by'))
            ->withDefault();
    }

    /**
     * author updated by
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, config('make_crud.columns.author.updated_by'))
            ->withDefault();
    }

    /**
     * author deleted by
     * @return BelongsTo
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, config('make_crud.columns.author.deleted_by'))
            ->withDefault();
    }

    /**
     * author restored by
     * @return BelongsTo
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, config('make_crud.columns.author.restored_by'))
            ->withDefault();
    }
}
