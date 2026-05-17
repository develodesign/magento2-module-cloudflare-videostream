<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_PATH_CUSTOMER_CODE = 'develo_cloudflare_video/general/customer_code';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Returns the Cloudflare Stream customer code, trimmed of surrounding whitespace.
     *
     * @return string
     */
    public function getCustomerCode(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_CODE));
    }
}
