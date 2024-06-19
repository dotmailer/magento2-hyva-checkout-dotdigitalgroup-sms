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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;

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

    public function shippingFormSubmit(array $data)
    {

        $this->updateCustomerAddress(
            $data['addressId'],
            $data['phoneNumber']
        );

        $this->updateValidity(true);
        $this->setPhoneNumber($data['phoneNumber']);
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->sessionCustomer->isLoggedIn();
    }

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


    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function updateValidity($validity = false)
    {
        $this->isValid = $validity;
    }

    public function doReset()
    {
        $this->reset(null,true);

    }

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
