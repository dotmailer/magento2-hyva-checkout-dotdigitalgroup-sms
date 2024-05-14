<?php

namespace Hyva\CheckoutDotdigitalgroupSms\Model\Form\EntityFormModifier;

use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;


class GuestFormModifier implements EntityFormModifierInterface
{
    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        $form->registerModificationListener(
            'dotdigital:guest-marketing-consent',
            'form:build',
            [$this, 'applyModification']
        );

        return $form;
    }

    public function applyModification(EntityFormInterface $form)
    {
        $checkboxField = $form->createField('dd_consent[dd_sms_consent_checkbox]', 'checkbox');
        $checkboxField->addData(['label' => 'My Checkbox',
            'type' => 'checkbox',
            'required' => false,
            'sortOrder' => 200,
            'visible' => true,
            'value' => false,
            'listeners' => [
                'change' => 'toggle_dd_consent',
                'click' => 'toggle_dd_consent',
            ],
            'click' => 'toggle_dd_consent',
            'change' => 'toggle_dd_consent',
        ]);

        $telephoneField = $form->createField('dd_consent[dd_sms_consent_telephone]', 'tel');
        $telephoneField->addData(['label' => 'Telephone',
            'type' => 'tel',
            'required' => true,
            'sortOrder' => 210,
            'visible' => true,
            'value' => '',
        ]);

        $telephoneField->setValidationRule('validate-phone-number-with-checkbox', 'true');
        $checkboxField->assignRelative($telephoneField);
        $form->addField($checkboxField);
        $form->addField($telephoneField);
    }
}
