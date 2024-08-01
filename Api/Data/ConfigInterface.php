<?php declare(strict_types=1);

namespace Basecom\CspSplitHeader\Api\Data;

/**
 * @api
 * @since 1.0.0
 */
interface ConfigInterface
{
    /**
     * Check if header splitting is enabled
     *
     * @return bool
     * @since 1.0.0
     */
    public function isHeaderSplittingEnabled();

    /**
     * Get max header size
     *
     * @return int
     * @since 1.0.0
     */
    public function getMaxHeaderSize();
}
