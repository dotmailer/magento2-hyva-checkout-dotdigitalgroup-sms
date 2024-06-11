<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Component;
use Dotdigitalgroup\Sms\ViewModel\TelephoneInputConfig;
use Dotdigitalgroup\Sms\ViewModel\Customer\Account\MarketingConsent as MarketingConsentViewModel;

class MarketingConsent extends Component
{

    /**
     * @var MarketingConsentViewModel
     */
    private $marketingConsent;

    public function __construct(
        MarketingConsentViewModel $marketingConsent
    ) {
        $this->marketingConsent = $marketingConsent;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function boot(): void
    {

        Parent::boot();
    }

    public function getMarketingConsentLabel(): string
    {
        return $this->marketingConsent->getSmsSignUpText();
    }

    public function getMarketingConsentText(): string
    {
        return $this->marketingConsent->getSmsMarketingConsentText();
    }

    public function getStoredMobileNumber(): string
    {
        return $this->marketingConsent->getStoredMobileNumber();
    }

    public function getIsSubscribed(): bool
    {
        return $this->marketingConsent->isSubscribed() ?? false;
    }

}
