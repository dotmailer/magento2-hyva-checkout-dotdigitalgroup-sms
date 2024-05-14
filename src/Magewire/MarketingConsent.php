<?php

declare(strict_types=1);

namespace Hyva\CheckoutDotdigitalgroupSms\Magewire;

use Magento\Framework\Exception\NoSuchEntityException;
use Magewirephp\Magewire\Component;

class MarketingConsent extends Component
{

    public $isCheckboxChecked = false;
    public $inputField = '';

    public function __construct() {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function boot(): void
    {

    }


}
