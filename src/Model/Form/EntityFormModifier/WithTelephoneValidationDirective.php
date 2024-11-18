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
use Dotdigitalgroup\Email\Helper\Config as DotdigitalCoreConfig;
use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\Config as EavConfig;

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
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
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
     * @throws LocalizedException
     */
    public function applyTelephoneValidationDirective(EntityFormInterface $form): EntityFormInterface
    {
        $dotdigitalEnabled = (bool) $this->scopeConfig->getValue(
            DotdigitalCoreConfig::XML_PATH_CONNECTOR_API_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );

        if (!$dotdigitalEnabled) {
            return $form;
        }

        /** @var \Hyva\Checkout\Model\Form\EntityField\Input $field */
        $field = $form->getField(AddressInterface::KEY_TELEPHONE);
        $validationEnabled = $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_SMS_PHONE_NUMBER_VALIDATION,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );

        $numberRequired = (bool)$this->eavConfig
            ->getAttribute('customer_address', 'telephone')
            ->getIsRequired();

        if ($validationEnabled) {
            $field->setAttribute('x-intl-input');
            $field->setValidationRule('validate-phone-number-with-checkbox');
        }

        if ($numberRequired) {
            $field->setValidationRule('required');
        }

        return $form;
    }
}
