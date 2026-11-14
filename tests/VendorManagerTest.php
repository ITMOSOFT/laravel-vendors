<?php

namespace ItmoSoft\Vendors\Tests;

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
    }

    public function test_moderation_approves_and_suspends_vendor(): void
    {
        $owner = User::create(['name' => 'Seller']);
        $manager = app(VendorManager::class);
        $vendor = $manager->create($owner, 'North Store');

        $manager->moderate($vendor, 'approved');
        $this->assertSame('approved', $vendor->fresh()->status);
        $this->assertNotNull($vendor->fresh()->approved_at);

        $manager->moderate($vendor->fresh(), 'suspended');
        $this->assertSame('suspended', $vendor->fresh()->status);
        $this->assertNull($vendor->fresh()->approved_at);
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
        $this->expectException(\InvalidArgumentException::class);

        $owner = User::create(['name' => 'Seller']);
        $manager = app(VendorManager::class);
        $vendor = $manager->create($owner, 'Invalid Status Store');
        $manager->moderate($vendor, 'active');
    }
}
