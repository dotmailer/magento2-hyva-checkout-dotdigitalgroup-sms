<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2022-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Model\Form\EntityFormModifier;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class WithTelephoneValidationDirective implements EntityFormModifierInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
       $this->scopeConfig = $scopeConfig;
       $this->storeManager = $storeManager;
    }

    /**
     * Apply modifiers to address forms on populate.
     *
     * @param EntityFormInterface $form
     * @return EntityFormInterface
     */
    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        $form->registerModificationListener(
            'applyTelephoneValidationDirective',
            'form:populate',
            [$this, 'applyTelephoneValidationDirective']
        );

        return $form;
    }

    /**
     * Apply dotdigital input validation directive.
     *
     * @param EntityFormInterface $form
     * @return EntityFormInterface
     * @throws NoSuchEntityException
     */
    public function applyTelephoneValidationDirective(EntityFormInterface $form): EntityFormInterface
    {
        $field = $form->getField(AddressInterface::KEY_TELEPHONE);
        $validationEnabled = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_SMS_PHONE_NUMBER_VALIDATION,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );

        if($validationEnabled){
            $field->setAttribute('x-intl-input');
        }

        return $form;
    }
}
