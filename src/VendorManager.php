<?php

namespace ItmoSoft\Vendors;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ItmoSoft\Vendors\Models\Vendor;

class VendorManager
{
    public function __construct(protected array $config)
    {
        $prefix = $this->config['table_prefix'] ?? 'itmosoft_';

        if (! is_string($prefix) || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new InvalidArgumentException(
                'The vendor table_prefix must contain only letters, numbers and underscores.'
            );
        }

        $allowed = $this->config['allowed_statuses']
            ?? ['pending', 'approved', 'rejected', 'suspended'];

        if (
            ! is_array($allowed)
            || $allowed === []
            || array_values($allowed) !== $allowed
            || count(array_unique($allowed, SORT_REGULAR)) !== count($allowed)
        ) {
            throw new InvalidArgumentException('The vendor allowed_statuses must be a non-empty list of unique strings.');
        }

        foreach ($allowed as $status) {
            if (! is_string($status) || trim($status) === '') {
                throw new InvalidArgumentException('Every configured vendor status must be a non-empty string.');
            }
        }

        $defaultStatus = $this->config['default_status'] ?? 'pending';

        if (! is_string($defaultStatus) || ! in_array($defaultStatus, $allowed, true)) {
            throw new InvalidArgumentException('The vendor default_status must be included in allowed_statuses.');
        }

        $this->config['allowed_statuses'] = $allowed;
        $this->config['default_status'] = $defaultStatus;
        $this->config['table_prefix'] = $prefix;
    }

    public function create(
        Model $owner,
        string $name,
        ?string $slug = null,
        array $attributes = []
    ): Vendor {
        if (! $owner->exists || $owner->getKey() === null) {
            throw new InvalidArgumentException('The vendor owner must be a persisted Eloquent model.');
        }

        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Vendor name cannot be empty.');
        }

        if (mb_strlen($name) > 255) {
            throw new InvalidArgumentException('Vendor name cannot exceed 255 characters.');
        }

        $slug = Str::slug($slug ?? $name);

        if ($slug === '') {
            throw new InvalidArgumentException('A valid vendor slug is required.');
        }

        if (mb_strlen($slug) > 255) {
            throw new InvalidArgumentException('Vendor slug cannot exceed 255 characters.');
        }

        $allowedAttributes = ['description', 'email', 'phone', 'website', 'metadata'];
        $unknownAttributes = array_diff(array_keys($attributes), $allowedAttributes);

        if ($unknownAttributes !== []) {
            throw new InvalidArgumentException(
                'Unsupported vendor attributes: '.implode(', ', $unknownAttributes).'.'
            );
        }

        foreach (['description', 'email', 'phone', 'website'] as $field) {
            if (array_key_exists($field, $attributes) && $attributes[$field] !== null && ! is_string($attributes[$field])) {
                throw new InvalidArgumentException("Vendor {$field} must be a string or null.");
            }
        }

        if (isset($attributes['email']) && mb_strlen($attributes['email']) > 255) {
            throw new InvalidArgumentException('Vendor email cannot exceed 255 characters.');
        }

        if (isset($attributes['phone']) && mb_strlen($attributes['phone']) > 50) {
            throw new InvalidArgumentException('Vendor phone cannot exceed 50 characters.');
        }

        if (isset($attributes['website']) && mb_strlen($attributes['website']) > 255) {
            throw new InvalidArgumentException('Vendor website cannot exceed 255 characters.');
        }

        $metadata = $attributes['metadata'] ?? [];

        if (! is_array($metadata)) {
            throw new InvalidArgumentException('Vendor metadata must be an array or null.');
        }

        $status = $this->config['default_status'];

        $vendor = new Vendor();
        $vendor->owner()->associate($owner);
        $vendor->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => $attributes['description'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'website' => $attributes['website'] ?? null,
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
            'metadata' => $metadata,
        ]);
        $vendor->save();

        return $vendor->refresh();
    }

    public function moderate(Vendor $vendor, string $status): Vendor
    {
        if (! in_array($status, $this->config['allowed_statuses'], true)) {
            throw new InvalidArgumentException("Unsupported vendor status [{$status}].");
        }

        $vendor->forceFill([
            'status' => $status,
            'approved_at' => $status === 'approved' ? ($vendor->approved_at ?? now()) : null,
        ])->save();

        return $vendor->refresh();
    }

    public function findBySlug(string $slug): ?Vendor
    {
        return Vendor::query()->where('slug', $slug)->first();
    }

    public function approved(): Builder
    {
        return Vendor::query()->approved();
    }
}
