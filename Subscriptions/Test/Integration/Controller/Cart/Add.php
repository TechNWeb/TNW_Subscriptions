<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Test\Integration\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use TNW\Subscriptions\Model\BillingFrequency;

/**
 * Test subscriptions add to cart controller.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class Add extends AbstractController
{
    /**
     * Test product can be added to subscription quote on storefront.
     *
     * @magentoDataFixture enableSubscriptions
     * @magentoDataFixture createProduct
     */
    public function testAddProductToGuestQuote()
    {
        $expectedResult = '{"error":false,"message":"You added s1 to your subscription cart."}';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('s1');
        /** @var BillingFrequency $billingFrequency */
        $billingFrequency = Bootstrap::getObjectManager()->create(BillingFrequency::class);
        $billingFrequency->load('test billing frequency', 'label');
        $this->getRequest()->setParams(
            [
                'billing_frequency_id' => $billingFrequency->getId(),
                'price' => '10',
                'trial_period' => '',
                'product_frequencies' =>
                    [
                        1 =>
                            [
                                'price' => '10',
                            ],
                    ],
                'period' => '1',
                'term' => '1',
                'start_on' => '1',
                'product_id' => $product->getId(),
                'qty' => '1',
                'form_key' => Bootstrap::getObjectManager()->get(FormKey::class)->getFormKey(),
            ]
        );
        $this->dispatch('tnw_subscriptions/cart/add');

        //Check quote for guest.
        $this->checkQuote(true);
        self::assertNotEmpty($this->getResponse()->getBody());
        self::assertEquals($expectedResult, $this->getResponse()->getBody());
    }

    /**
     * Test product can be added to subscription quote on storefront.
     *
     * @magentoDataFixture Magento/Customer/_files/customer_sample.php
     * @magentoDataFixture enableSubscriptions
     * @magentoDataFixture createProduct
     */
    public function testAddProductToCustomerQuote()
    {
        $expectedResult = '{"error":false,"message":"You added s1 to your subscription cart."}';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('s1');
        /** @var BillingFrequency $billingFrequency */
        $billingFrequency = Bootstrap::getObjectManager()->create(BillingFrequency::class);
        $billingFrequency->load('test billing frequency', 'label');
        $this->getRequest()->setParams(
            [
                'billing_frequency_id' => $billingFrequency->getId(),
                'price' => '10',
                'trial_period' => '',
                'product_frequencies' =>
                    [
                        1 =>
                            [
                                'price' => '10',
                            ],
                    ],
                'period' => '1',
                'term' => '1',
                'start_on' => '1',
                'product_id' => $product->getId(),
                'qty' => '1',
                'form_key' => Bootstrap::getObjectManager()->get(FormKey::class)->getFormKey(),
            ]
        );
        $this->loginCustomer();
        $this->dispatch('tnw_subscriptions/cart/add');
        //Check quote for customer.
        $this->checkQuote(false);
        self::assertNotEmpty($this->getResponse()->getBody());
        self::assertEquals($expectedResult, $this->getResponse()->getBody());
    }

    /**
     * Test product won't be added to subscription cart when subscriptions is disabled.
     *
     * @magentoDataFixture disableSubscriptions
     * @magentoDataFixture createProduct
     */
    public function testProductAddToCartWithDisabledSubscriptions()
    {
        $expectedResult = '{"error":true,"message":"We can\'t add this item to your subscription shopping cart right now."}';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('s1');
        /** @var BillingFrequency $billingFrequency */
        $billingFrequency = Bootstrap::getObjectManager()->create(BillingFrequency::class);
        $billingFrequency->load('test billing frequency', 'label');
        $this->getRequest()->setParams(
            [
                'billing_frequency_id' => $billingFrequency->getId(),
                'price' => '10',
                'trial_period' => '',
                'product_frequencies' =>
                    [
                        1 =>
                            [
                                'price' => '10',
                            ],
                    ],
                'period' => '1',
                'term' => '1',
                'start_on' => '1',
                'product_id' => $product->getId(),
                'qty' => '1',
                'form_key' => Bootstrap::getObjectManager()->get(FormKey::class)->getFormKey(),
            ]
        );
        $this->dispatch('tnw_subscriptions/cart/add');

        self::assertNotEmpty($this->getResponse()->getBody());
        self::assertEquals($expectedResult, $this->getResponse()->getBody());
    }

    /**
     * Disable subscriptions module.
     *
     * @return void
     */
    public static function disableSubscriptions()
    {
        include __DIR__ . '/../../_files/disable_subscriptions.php';
    }

    /**
     * Enable subscriptions module.
     *
     * @return void
     */
    public static function enableSubscriptions()
    {
        include __DIR__ . '/../../_files/enable_subscriptions.php';
    }

    /**
     * Create billing frequency, product billing frequency and product for subscription profile.
     *
     * @return void
     */
    public static function createProduct()
    {
        include  __DIR__ . '/../../_files/product.php';
    }

    /**
     * @return void
     */
    private function loginCustomer()
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = Bootstrap::getObjectManager()->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get('customer@example.com');

        /** @var \Magento\Customer\Model\Session $session */
        $session = Bootstrap::getObjectManager()->get(\Magento\Customer\Model\Session::class);
        $session->setCustomerData($customer);

        /** @var Session $checkoutSession */
        $checkoutSession = Bootstrap::getObjectManager()->get(Session::class);
        $checkoutSession->setCustomerData($customer);
    }

    /**
     * Check quote has or hasn't customer info, it's inactive and has correct items qty.
     *
     * @param bool $isGuest
     * @return void
     */
    private function checkQuote($isGuest)
    {
        /** @var CollectionFactory $quoteCollectionFactory */
        $quoteCollectionFactory = Bootstrap::getObjectManager()->get(CollectionFactory::class);
        $quoteCollection = $quoteCollectionFactory->create();
        self::assertEquals(1, $quoteCollection->getSize());
        /** @var Quote $quote */
        $quote = $quoteCollection->getFirstItem();
        if ($isGuest) {
            self::assertNull($quote->getCustomerEmail());
            self::assertEquals(0, $quote->getIsActive());
            self::assertEquals(1, $quote->getItemsQty());
        } else {
            self::assertEquals('customer@example.com', $quote->getCustomerEmail());
            self::assertEquals(0, $quote->getIsActive());
            self::assertEquals(1, $quote->getItemsQty());
        }
    }
}
