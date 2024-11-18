<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Dotdigitalgroup\Email\Helper\Config as DotdigitalCoreConfig;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as SessionCustomer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Sms\ViewModel\Customer\Account\MarketingConsent as MarketingConsentViewModel;
use Magewirephp\Magewire\Component\Form;

/**
 * Class MarketingConsent
 *
 * This class is a Magewire component that handles the marketing consent information.
 * It uses the MarketingConsentViewModel to get the marketing consent label, text,
 * stored mobile number, and subscription status.
 *
 */
class MarketingConsent extends Form
{
    /**
     * @var string[] $listeners
     */
    protected $listeners = [
        "update:marketing_consent_details" => "collectDetails"
    ];

    /**
     * @var string
     */
    public $marketingConsentLabel;

    /**
     * @var string
     */
    public $marketingConsentText;

    /**
     * @var string
     */
    public $marketingConsentPhoneNumber;

    /**
     * @var MarketingConsentViewModel
     */
    private $marketingConsent = false;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SessionCustomer
     */
    private $sessionCustomer;

    /**
     * MarketingConsent constructor.
     *
     * @param MarketingConsentViewModel $marketingConsent
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param SessionCustomer $sessionCustomer
     */
    public function __construct(
        MarketingConsentViewModel $marketingConsent,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        SessionCustomer $sessionCustomer,
    ) {
        $this->marketingConsent = $marketingConsent;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->sessionCustomer = $sessionCustomer;
    }

    /**
     * The boot method is called when the component is initialized.
     *
     * It checks if the customer is logged in and sets the phone number from the
     * shipping address in the checkout session
     */
    public function boot(): void
    {
        $this->collectDetails();
        parent::boot();
    }

    /**
     * Aggregate relative component information.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function collectDetails(): void
    {
        $this->marketingConsentLabel = $this->marketingConsent->getSmsSignUpText();
        $this->marketingConsentText = $this->marketingConsent->getSmsMarketingConsentText();
        $this->marketingConsentPhoneNumber = $this->marketingConsent->getStoredMobileNumber();

        if (empty($this->marketingConsentPhoneNumber)) {
            $this->marketingConsentPhoneNumber = $this->checkoutSession
                ->getQuote()
                ->getShippingAddress()
                ->getTelephone();
        }
    }

    /**
     * Save marketing consent on submit
     *
     * @param array $data
     * @return void
     */
    public function save($data = []): void
    {
        $marketingConsentPhoneNumber = (string)($data['marketingConsentPhoneNumber'] ?? '');
        $marketingConsent = (bool)($data['marketingConsent'] ?? false);
        $this->updateCustomerSession((string)$marketingConsent, $marketingConsentPhoneNumber);
    }

    /**
     * Get enabled state for consent and authentication at checkout
     *
     * @throws LocalizedException
     */
    public function shouldDisplay(): bool
    {
        $dotdigitalEnabled = (bool) $this->scopeConfig->getValue(
            DotdigitalCoreConfig::XML_PATH_CONNECTOR_API_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );

        $enabled = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_CHECKOUT_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );

        $addressId = $this->checkoutSession->getQuote()->getShippingAddress()->getCustomerAddressId();
        $isGuest = !$this->sessionCustomer->isLoggedIn();

        return ((!$addressId || $isGuest) && $enabled && $dotdigitalEnabled);
    }

    /**
     * Update customer session
     *
     * Update customer session with keys used in the dotdigital listeners to trigger the
     * save on the contact mobile number.
     *
     * @param string $marketingConsent
     * @param string $phoneNumber
     * @return void
     */
    private function updateCustomerSession(string $marketingConsent, string $phoneNumber)
    {
        // @phpstan-ignore-next-line
        $this->checkoutSession->setData('dd_sms_consent_checkbox', $marketingConsent);
        // @phpstan-ignore-next-line
        $this->checkoutSession->setData('dd_sms_consent_telephone', $phoneNumber);
    }
}
