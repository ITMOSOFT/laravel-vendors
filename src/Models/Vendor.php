<?php

namespace ItmoSoft\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Vendor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'approved_at' => 'immutable_datetime',
    ];

    public function getTable(): string
    {
        return config('itmosoft-vendors.table_prefix', 'itmosoft_').'vendors';
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->whereNotNull('approved_at');
    }
}
