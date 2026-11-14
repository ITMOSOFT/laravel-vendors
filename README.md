# ITMOSOFT Laravel Vendors

Vendor profiles, ownership, onboarding status and moderation for Laravel 12 and 13 marketplace applications.

## Features

- Create vendor profiles owned by any Eloquent model.
- Polymorphic owner relationship.
- Vendor name, slug, description and contact fields.
- Pending, approved, rejected and suspended statuses.
- Approve/reject/suspend vendor profiles.
- Query approved vendors.
- Find vendors by slug.
- JSON metadata.
- Configurable table prefix.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-vendors
php artisan vendor:publish --tag=itmosoft-vendors-config
php artisan migrate
```

By default, the migration creates `itmosoft_vendors`. Configure `table_prefix` before migrating if required.

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

New vendors default to `pending`.

## Moderate a vendor

```php
$manager->moderate($vendor, 'approved');
$manager->moderate($vendor, 'rejected');
$manager->moderate($vendor, 'suspended');
```

Only approved vendors have `approved_at` set.

## Query approved vendors

```php
$vendors = $manager->approved()->latest()->paginate(20);
$vendor = $manager->findBySlug('acme-home-goods');
```

## Important scope and production notes

- This package provides vendor profile persistence and status management, not a complete marketplace.
- Vendor registration routes, dashboards, authorization policies, KYC/document verification, commissions, payouts, product ownership, storefront pages and notifications are outside this package.
- Enforce unique slug generation and handle database unique-constraint errors if concurrent vendor registrations can create the same slug. This package does not automatically append a suffix.
- Restrict moderation actions to authorized administrators in the host application.
- The owner relation is polymorphic. Restrict allowed owner model types in your application.
- The package does not enforce vendor email uniqueness or verify ownership of email/website addresses.
- Add tenant scopes, audit logging, soft deletes and data-retention rules as required by your marketplace.

## Testing

```bash
composer install
composer validate --strict
composer test
```

## License

MIT. See [LICENSE](LICENSE).
