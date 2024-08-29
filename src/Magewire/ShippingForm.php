<?php

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Dotdigitalgroup\Email\Logger\Logger;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as SessionCustomer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;

/**
 * Class ShippingForm
 *
 * This class is a Magewire component that handles the shipping form.
 * It uses the SessionCustomer, AddressRepositoryInterface, ResultFactory, CartRepositoryInterface, and Session classes to manage the shipping form data.
 * The class includes methods for submitting the shipping form, checking if the customer is logged in, evaluating the completion of the form, setting the phone number, updating the validity of the form, and updating the customer address.
 *
 * @package Hyva\CheckoutDotdigitalgroupSms\Magewire
 */
class ShippingForm extends Form implements EvaluationInterface
{
    /**
     * @var string[]
     */

    protected $listeners = [
        'address_list_updated' => 'update',
    ];

    /**
     *
     */
    public $ready = false;

    /**
     * @var bool
     */
    public $isValid = false;


    /**
     * @var string
     */
    public $phoneNumber = '';

    /**
     * @var int
     */
    public $addressId = null;

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

     * ShippingForm constructor.
     *
     * @param SessionCustomer            $sessionCustomer
     * @param AddressRepositoryInterface $addressRepository
     * @param ResultFactory              $resultFactory
     * @param CartRepositoryInterface    $quoteRepository
     * @param Session                    $checkoutSession
     */
    public function __construct(
        SessionCustomer $sessionCustomer,
        AddressRepositoryInterface $addressRepository,
        ResultFactory $resultFactory,
        CartRepositoryInterface $quoteRepository,
        Session $checkoutSession,
        Logger $logger

    )
    {
        $this->sessionCustomer = $sessionCustomer;
        $this->addressRepository = $addressRepository;
        $this->resultFactory = $resultFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;

    }

    /**
     * The boot method is called when the component is initialized.
     * It checks if the customer is logged in and sets the phone number from the shipping address in the checkout session.
     *
     * @throws NoSuchEntityException
     */
    public function boot(): void
    {
        if (!$this->isCustomerLoggedIn()) {
            return;
        }

        $this->phoneNumber = $this->checkoutSession
            ->getQuote()
            ->getShippingAddress()
            ->getTelephone();

        $this->addressId = $this->checkoutSession
            ->getQuote()
            ->getShippingAddress()
            ->getCustomerAddressId();

        if(!empty($this->addressId))
        {
            $this->setPhoneNumber($this->checkoutSession
                ->getQuote()
                ->getShippingAddress()
                ->getTelephone()
            );
        }
        Parent::boot();
    }

    /**
     * The update method is called when the address list is updated.
     * It updates the phone number and address ID from the shipping address in the checkout session.
     */
    public function update()
    {
        $this->phoneNumber = $this->checkoutSession
            ->getQuote()
            ->getShippingAddress()
            ->getTelephone();

        $this->addressId = $this->checkoutSession
            ->getQuote()
            ->getShippingAddress()
            ->getCustomerAddressId();

        if(!empty($this->addressId))
        {
            $this->setPhoneNumber($this->checkoutSession
                ->getQuote()
                ->getShippingAddress()
                ->getTelephone()
            );
        }

        $this->isValid = false;
        $this->emit('check-validity');
        $this->emit('$refresh');
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
        $addressId = (string) array_key_exists('addressId', $data)
            ? $data['addressId'] : '';
        $addressPhoneNumber = (string) array_key_exists('phoneNumber', $data)
            ? $data['phoneNumber'] : '';
        $marketingConsentPhoneNumber = (string) array_key_exists('marketingConsentPhoneNumber', $data)
            ? $data['marketingConsentPhoneNumber'] : '';
        $marketingConsent = (bool) array_key_exists('marketingConsent', $data)
            ? true : '';

        $this->updateCustomerAddress($addressId,$addressPhoneNumber);
        $this->updateCustomerSession($marketingConsent,$marketingConsentPhoneNumber);
        $this->updateValidity(true);
        $this->setPhoneNumber($addressPhoneNumber);
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
        if ($this->isValid) {
            return $resultFactory->createSuccess();
        }

        return $resultFactory->createErrorMessage()
            ->withMessage(__('Shipping phone number is invalid. Please provide a valid phone number.'))
            ->withVisibilityDuration(5000)
            ->asWarning();
    }

    /**
     * The setPhoneNumber method sets the phone number.
     *
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * The updateValidity method updates the validity of the form.
     *
     * @param bool $validity
     */
    public function updateValidity($validity = false)
    {
        $this->isValid = $validity;
        $this->ready = true;
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
        if(!empty($addressId))
        {
            try {
                $address = $this->addressRepository->getById($addressId);
                $address->setTelephone($phoneNumber);
                $this->addressRepository->save($address);
            } catch (\Exeption $exception) {
                $this->logger->warning($exception->gerMessage());
            }
        }

        if(!empty($phoneNumber))
        {
            $quote = $this->checkoutSession->getQuote();
            $quote->getShippingAddress()->setTelephone($phoneNumber);
            $this->quoteRepository->save($quote);
        }
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
        $this->checkoutSession->setData('dd_sms_consent_checkbox',$marketingConsent);
        $this->checkoutSession->setData('dd_sms_consent_telephone',$phoneNumber);
    }
}
