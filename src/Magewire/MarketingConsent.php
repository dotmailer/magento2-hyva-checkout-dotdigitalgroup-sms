<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magewirephp\Magewire\Component;
use Dotdigitalgroup\Sms\ViewModel\Customer\Account\MarketingConsent as MarketingConsentViewModel;

/**
 * Class MarketingConsent
 *
 * This class is a Magewire component that handles the marketing consent information.
 * It uses the MarketingConsentViewModel to get the marketing consent label, text, stored mobile number, and subscription status.
 *
 */
class MarketingConsent extends Component
{
    public $isConsentEnabledAtCheckout  = false;

    /**
     * @var MarketingConsentViewModel
     */
    private $marketingConsent;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * MarketingConsent constructor.
     *
     * @param MarketingConsentViewModel $marketingConsent
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $session
     */
    public function __construct(
        MarketingConsentViewModel $marketingConsent,
        Session $checkoutSession
    ) {
        $this->marketingConsent = $marketingConsent;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get the marketing consent label from the MarketingConsentViewModel.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMarketingConsentLabel(): string
    {
        return $this->marketingConsent->getSmsSignUpText();
    }

    /**
     * Get the marketing consent text from the MarketingConsentViewModel.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMarketingConsentText(): string
    {
        return $this->marketingConsent->getSmsMarketingConsentText();
    }

    /**
     * Get the stored mobile number from the MarketingConsentViewModel.
     *
     * @return string|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getStoredMobileNumber(): string
    {
        $phoneNumber = $this->marketingConsent->getStoredMobileNumber();
        if (empty($phoneNumber)) {
            $phoneNumber = $this->checkoutSession
                ->getQuote()
                ->getShippingAddress()
                ->getTelephone();
        }

        return (string)$phoneNumber;
    }
}
