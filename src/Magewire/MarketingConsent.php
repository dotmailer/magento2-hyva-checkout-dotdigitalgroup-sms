<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Component;
use Dotdigitalgroup\Sms\ViewModel\TelephoneInputConfig;
use Dotdigitalgroup\Sms\ViewModel\Customer\Account\MarketingConsent as MarketingConsentViewModel;

/**
 * Class MarketingConsent
 *
 * This class is a Magewire component that handles the marketing consent information.
 * It uses the MarketingConsentViewModel to get the marketing consent label, text, stored mobile number, and subscription status.
 *
 * @package Hyva\CheckoutDotdigitalgroupSms\Magewire
 */
class MarketingConsent extends Component
{

    /**
     * @var MarketingConsentViewModel
     */
    private $marketingConsent;

    /**
     * MarketingConsent constructor.
     *
     * @param MarketingConsentViewModel $marketingConsent
     */
    public function __construct(
        MarketingConsentViewModel $marketingConsent
    ) {
        $this->marketingConsent = $marketingConsent;
    }

    /**
     * The boot method is called when the component is initialized.
     *
     * @throws NoSuchEntityException
     */
    public function boot(): void
    {
        Parent::boot();
    }

    /**
     * Get the marketing consent label from the MarketingConsentViewModel.
     *
     * @return string
     */
    public function getMarketingConsentLabel(): string
    {
        return $this->marketingConsent->getSmsSignUpText();
    }

    /**
     * Get the marketing consent text from the MarketingConsentViewModel.
     *
     * @return string
     */
    public function getMarketingConsentText(): string
    {
        return $this->marketingConsent->getSmsMarketingConsentText();
    }

    /**
     * Get the stored mobile number from the MarketingConsentViewModel.
     *
     * @return string
     */
    public function getStoredMobileNumber(): string
    {
        return $this->marketingConsent->getStoredMobileNumber();
    }

    /**
     * Check if the user is subscribed to marketing consent from the MarketingConsentViewModel.
     *
     * @return bool
     */
    public function getIsSubscribed(): bool
    {
        return $this->marketingConsent->isSubscribed() ?? false;
    }

}
