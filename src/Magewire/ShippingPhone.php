<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as SessionCustomer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Component\Form;

class ShippingPhone extends Form
{
    /**
     * @var string
     */
    public $phoneNumber = '';

    private $sessionCustomer;

    private $addressRepository;

    private $resultFactory;

    public function __construct(
        SessionCustomer $sessionCustomer,
        AddressRepositoryInterface $addressRepository,
        ResultFactory $resultFactory
    ) {
        $this->sessionCustomer = $sessionCustomer;
        $this->addressRepository = $addressRepository;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function boot(): void
    {
        if (!$this->isCustomerLoggedIn()) {
            return;
        }

        $this->phoneNumber = $this->sessionCustomer
            ->getCustomer()
            ->getPrimaryShippingAddress()
            ->getTelephone();

        Parent::boot();
    }

    public function updateTelephoneNumber($data): ResultInterface
    {
        $address = $this->addressRepository->getById($data['addressId']);
        $address->setTelephone($data['phoneNumber']);
        $this->addressRepository->save($address);
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHttpResponseCode(200);
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->sessionCustomer->isLoggedIn();
    }

    public function isNumberValid() {
        return preg_match('/^\+\d{1,3}[0-9]{7,12}$/', $this->phoneNumber);
    }
}
