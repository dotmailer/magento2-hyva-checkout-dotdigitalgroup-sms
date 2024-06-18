<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as SessionCustomer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Component\Form;

class ShippingPhone extends \Magewirephp\Magewire\Component implements \Hyva\Checkout\Model\Magewire\Component\EvaluationInterface
{

    protected $listeners = [
        'address_list_updated' => 'update',
        'shipping_phone_number_updated' => 'update'
    ];

    public $isValid = false;

    /**
     * @var string
     */
    public $phoneNumber = '';

    private $sessionCustomer;

    private $addressRepository;

    private $resultFactory;

    private $quoteRepository;

    private $checkoutSession;

    public function __construct(
        SessionCustomer $sessionCustomer,
        AddressRepositoryInterface $addressRepository,
        ResultFactory $resultFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
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

        $this->isValid = $this->isNumberValid();

        Parent::boot();
    }


    public function update()
    {
        $this->phoneNumber = $this->checkoutSession
            ->getQuote()
            ->getShippingAddress()
            ->getTelephone();
        $this->isValid = $this->isNumberValid();
    }

    public function formSubmit(array $data): ResultInterface
    {
        $this->updateCustomerAddress(
            $this->checkoutSession->getQuote()->getShippingAddress()->getId(),
            $data['phoneNumber']
        );

        $this->phoneNumber = $data['phoneNumber'];
        $this->isValid = $this->isNumberValid();

        return $this->resultFactory
            ->create(ResultFactory::TYPE_RAW)
            ->setHttpResponseCode(200);
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->sessionCustomer->isLoggedIn();
    }

    public function isNumberValid(): bool{
        return (bool) preg_match('/^\+\d{1,3}[0-9]{7,12}$/', $this->phoneNumber);
    }

    public function evaluateCompletion(\Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory $resultFactory): \Hyva\Checkout\Model\Magewire\Component\Evaluation\EvaluationResult
    {

        if ($this->isValid) {
            return $resultFactory->createSuccess();
        }

        return $resultFactory->createErrorMessage()
            ->withMessage(__('Shipping phone number is invalid. Please provide a valid phone number.'))
            ->withVisibilityDuration(5000)
            ->asWarning();

    }

    private function updateCustomerAddress($addressId, $phoneNumber)
    {
        $address = $this->addressRepository->getById($addressId);
        $address->setTelephone($phoneNumber);
        $this->addressRepository->save($address);
    }
}
