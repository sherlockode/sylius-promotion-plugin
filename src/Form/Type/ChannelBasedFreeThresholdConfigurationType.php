<?php

namespace Sherlockode\SyliusPromotionPlugin\Form\Type;

use Sylius\Bundle\CoreBundle\Form\Type\ChannelCollectionType;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelBasedFreeThresholdConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => FreeThresholdConfigurationType::class,
            'entry_options' => function (ChannelInterface $channel) {
                return [
                    'label' => $channel->getName(),
                    'currency' => $channel->getBaseCurrency()->getCode(),
                ];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ChannelCollectionType::class;
    }
}
