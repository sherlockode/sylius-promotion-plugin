<?php

namespace Sherlockode\SyliusPromotionPlugin\Form\Type;

use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class FreeThresholdConfigurationType extends AbstractType
{
    /**
     * @var RepositoryInterface
     */
    private $productRepository;

    /**
     * @param RepositoryInterface $productRepository
     */
    public function __construct(RepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('threshold', IntegerType::class, [
                'label' => 'sherlockode.free_threshold.threshold',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'numeric', 'groups' => ['sylius']]),
                    new Range([
                        'min' => 1,
                        'minMessage' => 'sherlockode.free_threshold.threshold_min',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'sherlockode.free_threshold.quantity',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'numeric', 'groups' => ['sylius']]),
                    new Range([
                        'min' => 1,
                        'minMessage' => 'sherlockode.free_threshold.quantity_min',
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('product_code', ProductAutocompleteChoiceType::class, [
                'label' => 'sylius.form.promotion_action.add_product_configuration.product',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'string', 'groups' => ['sylius']]),
                ],
            ])
        ;

        $builder->get('product_code')->addModelTransformer(
            new ReversedTransformer(new ResourceToIdentifierTransformer($this->productRepository, 'code'))
        );
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
        return 'sylius_promotion_action_free_threshold_configuration';
    }
}
