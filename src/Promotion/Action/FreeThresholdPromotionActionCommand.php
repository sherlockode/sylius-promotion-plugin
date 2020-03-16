<?php

namespace Sherlockode\SyliusPromotionPlugin\Promotion\Action;

use Sherlockode\SyliusPromotionPlugin\Manager\PromotionManager;
use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Component\Core\Distributor\IntegerDistributorInterface;
use Sylius\Component\Core\Distributor\ProportionalIntegerDistributorInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Promotion\Action\UnitDiscountPromotionActionCommand;
use Sylius\Component\Core\Promotion\Checker\Rule\ContainsProductRuleChecker;
use Sylius\Component\Core\Promotion\Filter\FilterInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;
use Sylius\Component\Promotion\Model\PromotionSubjectInterface;
use Sylius\Component\Resource\Exception\UnexpectedTypeException;
use Sylius\Component\Resource\Factory\FactoryInterface;

class FreeThresholdPromotionActionCommand extends UnitDiscountPromotionActionCommand
{
    public const TYPE = 'free_threshold';

    /**
     * @var FilterInterface
     */
    private $productFilter;

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var FactoryInterface
     */
    private $orderItemFactory;

    /**
     * @var OrderItemQuantityModifierInterface
     */
    private $itemQuantityModifier;

    /**
     * @var IntegerDistributorInterface
     */
    private $integerDistributor;

    /**
     * @var PromotionManager
     */
    private $promotionManager;

    /**
     * @var ProportionalIntegerDistributorInterface
     */
    private $proportionalIntegerDistributor;

    /**
     * @param FactoryInterface                        $adjustmentFactory
     * @param FilterInterface                         $productFilter
     * @param ObjectManager                           $om
     * @param FactoryInterface                        $orderItemFactory
     * @param OrderItemQuantityModifierInterface      $itemQuantityModifier
     * @param IntegerDistributorInterface             $integerDistributor
     * @param PromotionManager                        $promotionManager
     * @param ProportionalIntegerDistributorInterface $proportionalIntegerDistributor
     */
    public function __construct(
        FactoryInterface $adjustmentFactory,
        FilterInterface $productFilter,
        ObjectManager $om,
        FactoryInterface $orderItemFactory,
        OrderItemQuantityModifierInterface $itemQuantityModifier,
        IntegerDistributorInterface $integerDistributor,
        PromotionManager $promotionManager,
        ProportionalIntegerDistributorInterface $proportionalIntegerDistributor
    ) {
        parent::__construct($adjustmentFactory);

        $this->productFilter = $productFilter;
        $this->om = $om;
        $this->orderItemFactory = $orderItemFactory;
        $this->itemQuantityModifier = $itemQuantityModifier;
        $this->integerDistributor = $integerDistributor;
        $this->promotionManager = $promotionManager;
        $this->proportionalIntegerDistributor = $proportionalIntegerDistributor;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(PromotionSubjectInterface $subject, array $configuration, PromotionInterface $promotion): bool
    {
        if (!$subject instanceof OrderInterface) {
            throw new UnexpectedTypeException($subject, OrderInterface::class);
        }

        $channelCode = $subject->getChannel()->getCode();
        if (!isset($configuration[$channelCode]) ||
            !isset($configuration[$channelCode]['threshold']) ||
            !isset($configuration[$channelCode]['quantity']) ||
            !isset($configuration[$channelCode]['product_code'])
        ) {
            return false;
        }

        // Retrieve products that condition the rule
        $promotionRuleProducts = [];
        foreach ($promotion->getRules() as $rule) {
            if ($rule->getType() === ContainsProductRuleChecker::TYPE) {
                $promotionRuleProducts[] = $rule->getConfiguration()['product_code'];
            }
        }

        if (count($promotionRuleProducts) === 0) {
            return false;
        }

        $productFilter = [];
        $productFilter['filters']['products_filter']['products'] = $promotionRuleProducts;
        $filteredItems = $this->productFilter->filter($subject->getItems()->toArray(), $productFilter);

        if (empty($filteredItems)) {
            return false;
        }

        // Check if products conditioning the rule are in the correct quantity in the cart
        $thresholdFilteredItems = [];
        foreach ($filteredItems as $item) {
            if ($item->getQuantity() < $configuration[$channelCode]['threshold']) {
                continue;
            }
            $thresholdFilteredItems[] = $item;
        }

        if (count($thresholdFilteredItems) === 0) {
            return false;
        }

        $qtyToOffer = $configuration[$channelCode]['quantity'];
        $productToOffer = $configuration[$channelCode]['product_code'];

        // Retrieve products on which we should apply the rule
        $productFilter = [];
        $productFilter['filters']['products_filter']['products'] = [$productToOffer];
        $filteredItems = $this->productFilter->filter($subject->getItems()->toArray(), $productFilter);

        // Product to offer is not in cart yet
        // Get first variant from product and add it to cart
        if (empty($filteredItems)) {
            $orderItem = $this->createItem($subject, $productToOffer);
            if ($orderItem === null) {
                return false;
            }

            $this->updateItemQuantity($orderItem, $qtyToOffer);
            $subject->addItem($orderItem);
            $this->promotionManager->setShouldRelaunchPromotionProcessor(true);

            return false;
        }

        $itemsToOffer = [];
        $qties = [];
        $totalQty = 0;
        foreach ($filteredItems as $item) {
            $itemsToOffer[] = [
                'item' => $item,
                'qty' => $item->getQuantity(),
            ];
            $qties[] = $item->getQuantity();
            $totalQty += $item->getQuantity();
        }

        if ($totalQty < $qtyToOffer) {
            // Product to offer is in cart, but missing qties to offer
            // Get first matching item and add missing qty
            $itemData = $itemsToOffer[0];
            $missingQty = $qtyToOffer - $totalQty;
            $this->updateItemQuantity($itemData['item'], $itemData['qty'] + $missingQty);
            $this->promotionManager->setShouldRelaunchPromotionProcessor(true);

            return false;
        }

        $promotionAmount = $itemsToOffer[0]['item']->getUnitPrice() * $qtyToOffer;
        $distributedAmounts = $this->proportionalIntegerDistributor->distribute($qties, $promotionAmount);

        foreach ($itemsToOffer as $itemKey => $itemData) {
            $item = $itemData['item'];
            $promotionAmount = 0;
            if (isset($distributedAmounts[$itemKey])) {
                $promotionAmount = $distributedAmounts[$itemKey];
            }
            $splitPromotionAmounts = $this->integerDistributor->distribute($promotionAmount, $item->getQuantity());
            $i = 0;
            foreach ($item->getUnits() as $unit) {
                if (0 === $splitPromotionAmounts[$i]) {
                    continue;
                }
                $this->addAdjustmentToUnit($unit, $splitPromotionAmounts[$i], $promotion);
                $i++;
            }
        }

        return true;
    }

    /**
     * @param PromotionSubjectInterface $subject
     * @param string                    $productCode
     *
     * @return null|OrderItemInterface
     */
    private function createItem(PromotionSubjectInterface $subject, $productCode): ?OrderItemInterface
    {
        /** @var Product $product */
        $product = $this->om->getRepository(Product::class)->findOneBy([
            'code' => $productCode
        ]);

        if ($product === null) {
            return null;
        }

        if ($product->getVariants()->isEmpty()) {
            return null;
        }

        $variant = $product->getVariants()->first();
        if ($variant === null) {
            return null;
        }

        $orderItem = $this->orderItemFactory->createNew();

        /** @var OrderItemInterface $orderItem */
        $orderItem->setVariant($variant);

        return $orderItem;
    }

    /**
     * @param \Sylius\Component\Order\Model\OrderItemInterface $item
     * @param int                                              $quantity
     */
    private function updateItemQuantity(\Sylius\Component\Order\Model\OrderItemInterface $item, $quantity): void
    {
        $this->itemQuantityModifier->modify($item, $quantity);
    }
}
