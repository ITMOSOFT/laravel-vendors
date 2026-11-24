<?php

namespace ItmoSoft\Vendors\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Vendor extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'approved_at' => 'immutable_datetime',
    ];

    public function getTable(): string
    {
        $prefix = config('itmosoft-vendors.table_prefix', 'itmosoft_');

        if (! is_string($prefix) || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new InvalidArgumentException(
                'The vendor table_prefix must contain only letters, numbers and underscores.'
            );
        }

        return $prefix.'vendors';
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved')->whereNotNull('approved_at');
    }
}
