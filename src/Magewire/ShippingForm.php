<?php

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Hyva\Checkout\Model\Magewire\Component\Evaluation\EvaluationResult;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as SessionCustomer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
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

    protected $listeners = [
        'address_list_updated' => '$refresh'
    ];

    public $isValid = true;

    /**
     * @var string
     */
    public $phoneNumber = '';

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
     * ShippingForm constructor.
     *
     * @param SessionCustomer            $sessionCustomer
     * @param AddressRepositoryInterface $addressRepository
     * @param ResultFactory              $resultFactory
     * @param CartRepositoryInterface    $quoteRepository
     * @param Session                    $checkoutSession
     */
    public function __construct(
        SessionCustomer            $sessionCustomer,
        AddressRepositoryInterface $addressRepository,
        ResultFactory              $resultFactory,
        CartRepositoryInterface    $quoteRepository,
        Session                    $checkoutSession
    )
    {
        $this->sessionCustomer = $sessionCustomer;
        $this->addressRepository = $addressRepository;
        $this->resultFactory = $resultFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
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

        Parent::boot();
    }

    /**
     * The shippingFormSubmit method is called when the shipping form is submitted.
     * It updates the customer address with the provided address ID and phone number, updates the validity of the form to true, and sets the phone number.
     *
     * @param array $data
     */
    public function shippingFormSubmit(array $data)
    {

        $this->updateCustomerAddress(
            $data['addressId'],
            $data['phoneNumber']
        );

        $this->updateValidity(true);
        $this->setPhoneNumber($data['phoneNumber']);
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
     * @param string $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
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
    }

    /**
     * The updateCustomerAddress method updates the customer address with the provided address ID and phone number.
     * It saves the updated address in the address repository and the shipping address in the quote repository.
     *
     * @param int $addressId
     * @param string $phoneNumber
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function updateCustomerAddress($addressId, $phoneNumber)
    {
        $address = $this->addressRepository->getById($addressId);
        $address->setTelephone($phoneNumber);
        $this->addressRepository->save($address);

        $quote = $this->checkoutSession->getQuote();
        $quote->getShippingAddress()->setTelephone($phoneNumber);
        $this->quoteRepository->save($quote);
    }
}
