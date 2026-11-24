<?php

namespace ItmoSoft\Vendors\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use ItmoSoft\Vendors\Models\Vendor;
use ItmoSoft\Vendors\Tests\Models\User;
use ItmoSoft\Vendors\VendorManager;

class VendorManagerTest extends TestCase
{
    public function test_create_vendor_starts_pending_and_associates_owner(): void
    {
        $owner = User::create(['name' => 'Merchant']);
        $vendor = app(VendorManager::class)->create(
            $owner,
            'Acme Home Goods',
            null,
            ['email' => 'hello@example.test', 'metadata' => ['origin' => 'web']]
        );

        $this->assertSame('pending', $vendor->status);
        $this->assertSame('acme-home-goods', $vendor->slug);
        $this->assertSame($owner->getKey(), $vendor->owner->getKey());
        $this->assertSame(['origin' => 'web'], $vendor->metadata);
        $this->assertNull($vendor->approved_at);
    }

    public function test_create_vendor_trims_name_and_normalizes_explicit_slug(): void
    {
        $owner = User::create(['name' => 'Merchant']);
        $vendor = app(VendorManager::class)->create($owner, '  Acme Store  ', ' ACME Store! ');

        $this->assertSame('Acme Store', $vendor->name);
        $this->assertSame('acme-store', $vendor->slug);
    }

    public function test_create_rejects_empty_name_and_invalid_slug(): void
    {
        $owner = User::create(['name' => 'Merchant']);
        $manager = app(VendorManager::class);

        try {
            $manager->create($owner, '   ');
            $this->fail('An empty vendor name should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Vendor name cannot be empty.', $exception->getMessage());
        }

        try {
            $manager->create($owner, '!!!', '!!!');
            $this->fail('An empty normalized slug should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('A valid vendor slug is required.', $exception->getMessage());
        }
    }

    public function test_create_rejects_an_unsaved_owner(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The vendor owner must be a persisted Eloquent model.');

        app(VendorManager::class)->create(new User(['name' => 'Unsaved']), 'New Vendor');
    }

    public function test_create_rejects_invalid_attribute_types_and_unknown_keys(): void
    {
        $owner = User::create(['name' => 'Merchant']);
        $manager = app(VendorManager::class);

        try {
            $manager->create($owner, 'Bad Metadata', null, ['metadata' => 'not-an-array']);
            $this->fail('Non-array metadata should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Vendor metadata must be an array or null.', $exception->getMessage());
        }

        try {
            $manager->create($owner, 'Bad Email', null, ['email' => ['not', 'a', 'string']]);
            $this->fail('Non-string email should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Vendor email must be a string or null.', $exception->getMessage());
        }

        try {
            $manager->create($owner, 'Typo Attribute', null, ['emial' => 'typo@example.test']);
            $this->fail('Unknown attributes should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Unsupported vendor attributes', $exception->getMessage());
        }
    }

    public function test_moderation_approves_and_suspends_vendor(): void
    {
        $owner = User::create(['name' => 'Seller']);
        $manager = app(VendorManager::class);
        $vendor = $manager->create($owner, 'North Store');

        $manager->moderate($vendor, 'approved');
        $approved = $vendor->fresh();

        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);

        $manager->moderate($approved, 'suspended');
        $suspended = $vendor->fresh();

        $this->assertSame('suspended', $suspended->status);
        $this->assertNull($suspended->approved_at);
    }

    public function test_reapproving_vendor_preserves_original_approval_timestamp(): void
    {
        $owner = User::create(['name' => 'Seller']);
        $manager = app(VendorManager::class);
        $vendor = $manager->create($owner, 'North Store');
        $manager->moderate($vendor, 'approved');

        $originalApproval = $vendor->fresh()->approved_at->toDateTimeString();

        $manager->moderate($vendor->fresh(), 'approved');

        $this->assertSame($originalApproval, $vendor->fresh()->approved_at->toDateTimeString());
    }

    public function test_find_by_slug_and_approved_query(): void
    {
        $owner = User::create(['name' => 'Seller']);
        $manager = app(VendorManager::class);
        $vendor = $manager->create($owner, 'Bright Lights');
        $manager->moderate($vendor, 'approved');

        $this->assertSame($vendor->getKey(), $manager->findBySlug('bright-lights')->getKey());
        $this->assertCount(1, $manager->approved()->get());
    }

    public function test_invalid_status_is_rejected(): void
    {
        $owner = User::create(['name' => 'Seller']);
        $manager = app(VendorManager::class);
        $vendor = $manager->create($owner, 'Invalid Status Store');

        $this->expectException(InvalidArgumentException::class);
        $manager->moderate($vendor, 'active');
    }

    public function test_manager_rejects_invalid_status_configuration(): void
    {
        $invalidConfigurations = [
            ['allowed_statuses' => []],
            ['allowed_statuses' => ['pending', 'pending']],
            ['allowed_statuses' => ['pending', 123]],
            ['allowed_statuses' => ['pending'], 'default_status' => 'approved'],
            ['allowed_statuses' => 'pending,approved'],
        ];

        foreach ($invalidConfigurations as $configuration) {
            try {
                new VendorManager($configuration);
                $this->fail('Invalid vendor status configuration was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function test_manager_rejects_invalid_table_prefix_configuration(): void
    {
        foreach (['bad-prefix-', 'prefix.name', 'prefix name', 123] as $prefix) {
            try {
                new VendorManager(['table_prefix' => $prefix]);
                $this->fail('An invalid table prefix was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('table_prefix', $exception->getMessage());
            }
        }
    }

    public function test_approved_default_status_sets_approval_timestamp(): void
    {
        $owner = User::create(['name' => 'Seller']);
        $manager = new VendorManager([
            'default_status' => 'approved',
            'allowed_statuses' => ['pending', 'approved', 'rejected', 'suspended'],
        ]);

        $vendor = $manager->create($owner, 'Preapproved Store');

        $this->assertSame('approved', $vendor->status);
        $this->assertNotNull($vendor->approved_at);
    }

    public function test_model_table_prefix_is_validated(): void
    {
        config(['itmosoft-vendors.table_prefix' => 'invalid-prefix-']);

        $this->expectException(InvalidArgumentException::class);
        (new Vendor())->getTable();
    }
}
