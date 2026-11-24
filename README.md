# ITMOSOFT Laravel Vendors

Vendor profiles, ownership, onboarding status and moderation for Laravel 12 and 13 marketplace applications.

## Features

- Create vendor profiles owned by any persisted Eloquent model.
- Polymorphic owner relationship.
- Vendor name, slug, description and contact fields.
- Pending, approved, rejected and suspended statuses.
- Approve, reject and suspend vendor profiles.
- Query approved vendors and find vendors by slug.
- JSON metadata and configurable table prefix.
- Validate package configuration and vendor input before persistence.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-vendors
php artisan vendor:publish --tag=itmosoft-vendors-config
php artisan migrate
```

By default, the migration creates `itmosoft_vendors`. Configure `table_prefix` before migrating if required. The prefix may contain only ASCII letters, numbers and underscores; an empty prefix is allowed.

## Create a vendor

```php
use ItmoSoft\Vendors\VendorManager;

$manager = app(VendorManager::class);

$vendor = $manager->create(
    owner: auth()->user(),
    name: 'Acme Home Goods',
    attributes: [
        'description' => 'Home and lifestyle products.',
        'email' => 'hello@example.com',
        'phone' => '+1 555 0100',
        'website' => 'https://example.com',
        'metadata' => ['registration_source' => 'seller-portal'],
    ],
);
```

The owner must already be persisted. New vendors default to `pending`.

Supported attributes are `description`, `email`, `phone`, `website`, and `metadata`. Contact fields must be strings or null; metadata must be an array or null. Unknown attribute keys are rejected.

## Moderate a vendor

```php
$manager->moderate($vendor, 'approved');
$manager->moderate($vendor, 'rejected');
$manager->moderate($vendor, 'suspended');
```

Only approved vendors have `approved_at` set. Re-approving an already approved vendor preserves its original approval timestamp.

## Query approved vendors

```php
$vendors = $manager->approved()->latest()->paginate(20);
$vendor = $manager->findBySlug('acme-home-goods');
```

## Configuration

The published `config/itmosoft-vendors.php` file supports:

```php
return [
    'table_prefix' => 'itmosoft_',
    'default_status' => 'pending',
    'allowed_statuses' => ['pending', 'approved', 'rejected', 'suspended'],
];
```

`default_status` must be present in `allowed_statuses`. The allowed status list must be a non-empty list of unique, non-empty strings. The table prefix may contain only letters, numbers and underscores.

## Important scope and production notes

- This package provides vendor profile persistence and status management, not a complete marketplace.
- Vendor registration routes, dashboards, authorization policies, KYC/document verification, commissions, payouts, product ownership, storefront pages and notifications are outside this package.
- Slugs are protected by a database unique constraint. Concurrent duplicate slug attempts can still raise a database unique-constraint exception; handle that exception in the host application and retry with a different slug if desired.
- Restrict moderation actions to authorized administrators in the host application.
- The owner relation is polymorphic. Restrict allowed owner model types in your application.
- The package does not verify ownership of email or website addresses.
- Add tenant scopes, audit logging, soft deletes and data-retention rules as required by your marketplace.

## Testing

```bash
composer install
composer validate --strict
composer test
```

## License

MIT. See [LICENSE](LICENSE).
