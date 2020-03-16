<?php

namespace Sherlockode\SyliusPromotionPlugin\Manager;

class PromotionManager
{
    /**
     * @var bool
     */
    private $shouldRelaunchPromotionProcessor;

    public function __construct()
    {
        $this->shouldRelaunchPromotionProcessor = false;
    }

    /**
     * @param bool $shouldRelaunchPromotionProcessor
     *
     * @return $this
     */
    public function setShouldRelaunchPromotionProcessor(bool $shouldRelaunchPromotionProcessor): self
    {
        $this->shouldRelaunchPromotionProcessor = $shouldRelaunchPromotionProcessor;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldRelaunchPromotionProcessor(): bool
    {
        return $this->shouldRelaunchPromotionProcessor;
    }
}
