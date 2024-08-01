<?php declare(strict_types=1);

namespace Basecom\CspSplitHeader\Model;

use Basecom\CspSplitHeader\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ConfigInterface
{
    public const XML_PATH_HEADER_SPLITTING_ENABLE = 'basecom_csp_split_header/settings/header_splitting_enable';
    public const XML_PATH_MAX_HEADER_SIZE = 'basecom_csp_split_header/settings/max_header_size';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Check if header splitting is enabled
     *
     * @return bool
     */
    public function isHeaderSplittingEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_HEADER_SPLITTING_ENABLE);
    }

    /**
     * Get max header size
     *
     * @return int
     */
    public function getMaxHeaderSize(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_MAX_HEADER_SIZE);
    }
}
