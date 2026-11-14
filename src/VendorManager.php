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
    }

    public function create(
        Model $owner,
        string $name,
        ?string $slug = null,
        array $attributes = []
    ): Vendor {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Vendor name cannot be empty.');
        }

        $slug = Str::slug($slug ?: $name);
        if ($slug === '') {
            throw new InvalidArgumentException('A valid vendor slug is required.');
        }

        $vendor = new Vendor();
        $vendor->owner()->associate($owner);
        $vendor->name = $name;
        $vendor->slug = $slug;
        $vendor->description = $attributes['description'] ?? null;
        $vendor->email = $attributes['email'] ?? null;
        $vendor->phone = $attributes['phone'] ?? null;
        $vendor->website = $attributes['website'] ?? null;
        $vendor->status = $this->config['default_status'] ?? 'pending';
        $vendor->metadata = $attributes['metadata'] ?? [];
        $vendor->save();

        return $vendor->refresh();
    }

    public function moderate(Vendor $vendor, string $status): Vendor
    {
        $allowed = $this->config['allowed_statuses'] ?? ['pending', 'approved', 'rejected', 'suspended'];
        if (! in_array($status, $allowed, true)) {
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
