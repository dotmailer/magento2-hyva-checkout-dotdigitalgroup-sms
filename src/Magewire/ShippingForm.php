<?php

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\ViewModel\Customer\Account\MarketingConsent;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\EvaluationResult;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as SessionCustomer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magewirephp\Magewire\Component\Form;

/**
 * Class ShippingForm
 *
 * This class is a Magewire component that handles the shipping form.
 * It uses the SessionCustomer, AddressRepositoryInterface, ResultFactory, CartRepositoryInterface, and Session classes to manage the shipping form data.
 * The class includes methods for submitting the shipping form, checking if the customer is logged in, evaluating the completion of the form, setting the phone number, updating the validity of the form, and updating the customer address.
 *
 */
class ShippingForm extends Form implements EvaluationInterface
{

    /**
     * @var string[]
     */
    protected $listeners = [
        'address_list_updated' => 'update',
        'update_details' => 'update'
    ];

    /**
     * @var bool
     */
    public $isValid = false;

    /**
     * @var bool
     */
    public $isGuestCheckout = false;

    /**
     * @var string
     */
    public $phoneNumber = '';

    /**
     * @var int
     */
    public $addressId = null;

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
     * @var SessionCustomer
     */
    private $sessionCustomer;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MarketingConsent
     */
    private $marketingConsent;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ShippingForm constructor.
     *
     * @param SessionCustomer $sessionCustomer
     * @param AddressRepositoryInterface $addressRepository
     * @param ResultFactory $resultFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param MarketingConsent $marketingConsent
     * @param EavConfig $eavConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SessionCustomer $sessionCustomer,
        AddressRepositoryInterface $addressRepository,
        ResultFactory $resultFactory,
        CartRepositoryInterface $quoteRepository,
        Session $checkoutSession,
        Logger $logger,
        MarketingConsent $marketingConsent,
        EavConfig $eavConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->sessionCustomer = $sessionCustomer;
        $this->addressRepository = $addressRepository;
        $this->resultFactory = $resultFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->marketingConsent = $marketingConsent;
        $this->eavConfig = $eavConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * The boot method is called when the component is initialized.
     *
     * It checks if the customer is logged in and sets the phone number from the
     * shipping address in the checkout session
     */
    public function boot(): void
    {
        $this->collectAddressDetails();
        $this->collectConsentDetails();
        parent::boot();
    }

    /**
     * Collect phone number and address id for shipping form additions.
     *
     * @return void
     */
    public function collectAddressDetails(): void
    {
        try {
            $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
            $this->addressId = $shippingAddress->getCustomerAddressId();

            if (!empty($this->addressId)) {
                $this->phoneNumber = $shippingAddress->getTelephone();
            }
        } catch ( NoSuchEntityException | LocalizedException $exception )
        {
            $this->logger->error($exception);
        } finally {
            $this->emit('$refresh');
        }

    }


    /**
     * Collect marking consent details
     *
     * @return void
     */
    public function collectConsentDetails()
    {
        try {
            $this->marketingConsentLabel = $this->marketingConsent->getSmsSignUpText();
            $this->marketingConsentText = $this->marketingConsent->getSmsMarketingConsentText();
            $this->marketingConsentPhoneNumber = $this->marketingConsent->getStoredMobileNumber();

            if (empty($this->phoneNumber)) {
                $this->marketingConsentPhoneNumber = $this->phoneNumber;
            }
        } catch ( NoSuchEntityException | LocalizedException $exception )
        {
            $this->logger->error($exception);
        }
    }

    /**
     * The update method is called when the address list is updated.
     * It updates the phone number and address ID from the shipping address in the checkout session.
     */
    public function update(): void
    {
        $this->isValid = false;
        $this->collectAddressDetails();
        $this->emit('validate.phone_number');
    }

    /**
     * The shippingFormSubmit method is called when the shipping form is submitted.
     * It updates the customer address with the provided address ID and phone number, updates the validity of the form to true, and sets the phone number.
     *
     * @param array $data
     * @return void|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function shippingFormSubmit(array $data)
    {
        $addressId = (string)($data['addressId'] ?? '');
        $addressPhoneNumber = (string)($data['phoneNumber'] ?? '');
        $marketingConsentPhoneNumber = (string)($data['marketingConsentPhoneNumber'] ?? '');
        $marketingConsent = (bool)($data['marketingConsent'] ?? false);

        $this->updateCustomerAddress($addressId, $addressPhoneNumber);
        $this->updateCustomerSession($marketingConsent, $marketingConsentPhoneNumber);
        $this->phoneNumber = $addressPhoneNumber;
        $this->updateValidity(true);
    }

    /**
     * The isCustomerLoggedIn method checks if the customer is logged in.
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->sessionCustomer->isLoggedIn();
    }

    /**
     * The evaluateCompletion method is called to evaluate the completion of the form.
     * It returns a success result if the form is valid, otherwise it returns an error message.
     *
     * @param EvaluationResultFactory $resultFactory
     * @return EvaluationResult
     */
    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResult
    {
        if ($this->isGuestCheckout){
            return $resultFactory->createSuccess();
        }

        if ($this->isValid) {
            return $resultFactory->createSuccess();
        }

        return $resultFactory->createErrorMessage()
            ->withMessage(__('Shipping phone number is invalid. Please provide a valid phone number.'))
            ->withVisibilityDuration(5000)
            ->asWarning();
    }

    /**
     * The updateValidity method updates the validity of the form.
     *
     * @param bool $validity
     */
    public function updateValidity($validity = false)
    {
        $this->isValid = $validity;
    }

    /**
     * Get validation configuration for shipping input
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getValidationConfig()
    {

        $numberRequired = (bool)$this->eavConfig
            ->getAttribute('customer_address', 'telephone')
            ->getIsRequired();

        $validationSet = [
            "validate-phone-number-with-checkbox" => (bool)$this->marketingConsent->isPhoneNumberValidationEnabled()
        ];

        if ($numberRequired) {
            $validationSet['required'] = true;
        }

        return htmlspecialchars(json_encode($validationSet, JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Should display the component.
     *
     * @return bool
     */
    public function shouldDisplay(): bool
    {
        $hasAddress = !empty($this->addressId);
        return $hasAddress;
    }

    /**
     * The updateCustomerAddress method updates the customer address with the provided address ID and phone number.
     * It saves the updated address in the address repository and the shipping address in the quote repository.
     *
     * @param string|null $addressId
     * @param string|null $phoneNumber
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function updateCustomerAddress(?string $addressId, ?string $phoneNumber)
    {
        if (!empty($addressId)) {
            try {
                $address = $this->addressRepository->getById($addressId);
                $address->setTelephone($phoneNumber);
                $this->addressRepository->save($address);
            } catch (NoSuchEntityException | LocalizedException $exception) {
                $this->logger->warning($exception);
            }
        }

        if (is_null($phoneNumber)) {
            $phoneNumber = '';
        }

        $quote = $this->checkoutSession->getQuote();
        $quote->getShippingAddress()->setTelephone($phoneNumber);
        $this->quoteRepository->save($quote);
    }

    /**
     * Update customer session with keys used in the dotdigital listeners to trigger the
     * save on the contact mobile number.
     *
     * @param string $marketingConsent
     * @param string $phoneNumber
     * @return void
     */
    private function updateCustomerSession(string $marketingConsent, string $phoneNumber)
    {
        $this->checkoutSession->setData('dd_sms_consent_checkbox', $marketingConsent);
        $this->checkoutSession->setData('dd_sms_consent_telephone', $phoneNumber);
    }
}
