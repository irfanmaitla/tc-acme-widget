<?php

declare(strict_types=1);

namespace AcmeWidgetCo\Tests\Unit\Bundles;

use AcmeWidgetCo\Bundles\ProductBundle;
use AcmeWidgetCo\Bundles\BundleManager;
use AcmeWidgetCo\Models\Product;
use PHPUnit\Framework\TestCase;

/**
 * Partial test coverage (~20%) for Bundle feature
 * 
 * TODO: Add more comprehensive tests to reach 90% coverage
 * - Test edge cases in canApplyToItems
 * - Test getMaxApplications with various scenarios
 * - Test calculateSavings with different product combinations
 * - Test BundleManager's applyBestBundle with multiple bundles
 * - Test calculateWithBundles with complex scenarios
 * - Test removeBundle and hasBundle methods
 * - Test error handling and validation
 */
class BundleTest extends TestCase
{
    public function test_can_create_product_bundle(): void
    {
        $bundle = new ProductBundle(
            'BUNDLE01',
            'Widget Starter Pack',
            ['R01', 'G01', 'B01'],
            55.00
        );

        $this->assertEquals('BUNDLE01', $bundle->getCode());
        $this->assertEquals('Widget Starter Pack', $bundle->getName());
        $this->assertEquals(['R01', 'G01', 'B01'], $bundle->getProductCodes());
        $this->assertEquals(55.00, $bundle->getBundlePrice());
    }

    public function test_bundle_throws_exception_for_empty_products(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bundle must contain at least one product');

        new ProductBundle(
            'BUNDLE01',
            'Empty Bundle',
            [],
            50.00
        );
    }

    public function test_can_add_bundle_to_manager(): void
    {
        $bundle = new ProductBundle(
            'BUNDLE01',
            'Widget Starter Pack',
            ['R01', 'G01'],
            50.00
        );

        $manager = new BundleManager();
        $manager->addBundle($bundle);

        $this->assertEquals(1, $manager->getBundleCount());
        $this->assertSame($bundle, $manager->getBundle('BUNDLE01'));
    }

    public function test_can_check_if_bundle_applies_to_items(): void
    {
        $bundle = new ProductBundle(
            'BUNDLE01',
            'Widget Pack',
            ['R01', 'G01'],
            50.00
        );

        $this->assertTrue($bundle->canApplyToItems(['R01', 'G01', 'B01']));
        $this->assertFalse($bundle->canApplyToItems(['R01']));
    }

    // NOTE: Many methods are NOT tested yet - intentionally low coverage
    // The following methods have NO tests:
    // - ProductBundle::calculateSavings()
    // - ProductBundle::getMaxApplications()
    // - BundleManager::findApplicableBundles()
    // - BundleManager::applyBestBundle()
    // - BundleManager::calculateWithBundles()
    // - BundleManager::removeBundle()
    // - BundleManager::hasBundle()
    // - Edge cases and error scenarios
}