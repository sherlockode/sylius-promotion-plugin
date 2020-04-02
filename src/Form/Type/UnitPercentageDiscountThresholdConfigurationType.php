<?php

namespace Sherlockode\SyliusPromotionPlugin\Form\Type;

use Sylius\Bundle\PromotionBundle\Form\Type\PromotionFilterCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class UnitPercentageDiscountThresholdConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('threshold', IntegerType::class, [
                'label' => 'sherlockode.item_percentage_discount_threshold.threshold',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'numeric', 'groups' => ['sylius']]),
                    new Range([
                        'min' => 1,
                        'minMessage' => 'sherlockode.item_percentage_discount_threshold.threshold_min',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('filters', PromotionFilterCollectionType::class, [
                'label' => false,
                'required' => false,
                'currency' => $options['currency'],
            ])
            ->add('percentage', PercentType::class, [
                'label' => 'sylius.form.promotion_action.percentage_discount_configuration.percentage',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'numeric', 'groups' => ['sylius']]),
                    new Range([
                        'min' => 0,
                        'max' => 1,
                        'minMessage' => 'sylius.promotion_action.percentage_discount_configuration.min',
                        'maxMessage' => 'sylius.promotion_action.percentage_discount_configuration.max',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('currency')
            ->setAllowedTypes('currency', 'string')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'sylius_promotion_action_unit_percentage_discount_threshold_configuration';
    }
}
