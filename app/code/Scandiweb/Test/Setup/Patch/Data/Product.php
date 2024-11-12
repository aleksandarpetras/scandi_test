<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\State;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class AddSimpleProduct implements DataPatchInterface
{
    private const SKU = 'product-123';
    private const PRODUCT_NAME = 'Product';
    private const PRICE = 50.00;
    private const CATEGORY_ID = 2;
    private const QTY = 20;
    private const SOURCE_QTY = 20;
    private const ATTRIBUTE_SET_ID = 4;

    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * Constructor for Product.
     *
     * @param ProductInterfaceFactory    $productInterfaceFactory  Factory for creating product instances
     * @param ProductRepositoryInterface $productRepository        Repository for saving and retrieving products
     * @param CategoryFactory            $categoryFactory          Factory for creating category instances
     * @param State                      $state                    Application state, used to set the area code
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface   $sourceItemsSaveInterface
     */
    public function __construct(
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        CategoryFactory $categoryFactory,
        State $state,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
    ) {
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->categoryFactory = $categoryFactory;
        $this->state = $state;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        // Emulate the 'adminhtml' area code to ensure that the product creation process works correctly
        $this->state->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * Executes the product creation logic under the 'adminhtml' area code.
     *
     * @return void
     */
    public function execute(): void
    {
        $product = $this->productInterfaceFactory->create();

        if ($this->productRepository->getIdBySku(self::SKU)) {
            return; // If product exists, skip creation
        };

        $product->setSku(self::SKU);
        $product->setName(self::PRODUCT_NAME);
        $product->setPrice(self::PRICE);
        $product->setAttributeSetId(self::ATTRIBUTE_SET_ID); // Default attribute set set ID
        $product->setStatus(Status::STATUS_ENABLED); // Enable product
        $product->setVisibility(Visibility::VISIBILITY_BOTH); // Catalog and Search
        $product->setTypeId(Type::TYPE_SIMPLE);
        $product->setStockData(['qty' => self::QTY, 'is_in_stock' => 1]);

        // Assign the product to the "Default Category"
        $category = $this->categoryFactory->create()->load(self::CATEGORY_ID);
        $product->setCategoryIds([$category->getId()]);

        // Save the product with the category assignment
        $product = $this->productRepository->save($product);

        // Create and configure source item for inventory
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default'); // Set the default source code
        $sourceItem->setQuantity(self::SOURCE_QTY); // Set the quantity
        $sourceItem->setSku($product->getSku()); // Link the product SKU to the source item
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK); // Set stock status to "In Stock"

        // Save the source item
        $this->sourceItemsSaveInterface->execute([$sourceItem]);
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}