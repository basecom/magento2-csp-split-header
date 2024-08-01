<?php declare(strict_types=1);

namespace Basecom\CspSplitHeader\Plugin\Model\Policy\Renderer;

use Basecom\CspSplitHeader\Model\Config;
use Basecom\CspSplitHeader\Plugin\Model\Policy\PolicyHeader;
use Laminas\Http\AbstractMessage;
use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\Http\Header\ContentSecurityPolicyReportOnly;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Loader\PluginClassLoader;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Policy\Renderer\SimplePolicyHeaderRenderer;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

/**
 * Plugin for Simple Policy Header
 */
class CspHeaderSplitter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Config $config,
    ) {
    }

    private const PLUGINS = [
        'contentsecuritypolicyreportonly' => ContentSecurityPolicyReportOnly::class,
        'contentsecuritypolicy'           => ContentSecurityPolicy::class,
    ];

    private array $contentHeaders = [];

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param null $result
     */
    public function afterRender(
        SimplePolicyHeaderRenderer $subject,
        $result,
        PolicyInterface $policy,
        HttpResponse $response
    ): void {
        $headerName = $this->getHeaderName($response);
        /** @var HeaderInterface $header */
        $header = $response->getHeader($headerName);
        $policyValue = $header->getFieldValue();
        $isHeaderSplittingEnabled = $this->config->isHeaderSplittingEnabled();

        $maxHeaderSize = $this->config->getMaxHeaderSize();
        $currentHeaderSize = strlen($policyValue);

        if ($isHeaderSplittingEnabled) {
            $this->registerCspHeaderPlugins($response);
            $this->splitUpCspHeaders($response, $policy->getId(), $policyValue);
        } else {
            if ($maxHeaderSize >= $currentHeaderSize) {
                $response->setHeader($headerName, $policyValue, true);
            } else {
                $this->logger->error(
                    sprintf(
                        'Unable to set the CSP header. The header size of %d bytes exceeds the '.
                        'maximum size of %d bytes.',
                        $currentHeaderSize,
                        $maxHeaderSize
                    )
                );
            }
        }
    }

    /**
     * The CSP headers normally use the GenericHeader class, which does not support multi-header values.
     * The Laminas framework includes multi-value supported special classes for CSP headers.
     * With this registration we enable the usage of the special classes by registering the definitions to the
     * plugin loader class.
     */
    private function registerCspHeaderPlugins(HttpResponse $response): void
    {
        /** @var AbstractMessage $response */
        /** @var PluginClassLoader $pluginClassLoader */
        $pluginClassLoader = $response->getHeaders()->getPluginClassLoader();
        $pluginClassLoader->registerPlugins(self::PLUGINS);
    }

    /**
     * Make sure that the CSP headers are handled as several headers ("multi-header")
     */
    private function splitUpCspHeaders(HttpResponse $response, string $policyId, string $policyValue): void
    {
        $headerName = $this->getHeaderName($response);

        if (!$headerName) {
            return;
        }

        $newHeader = $policyId.' '.$policyValue.';';
        $maxHeaderSize = $this->config->getMaxHeaderSize();
        $newHeaderSize = strlen($policyValue);

        if ($newHeaderSize <= $maxHeaderSize) {
            $this->contentHeaders[] = $newHeader;
        } else {
            $this->logger->error(
                sprintf(
                    'Unable to set the CSP header. The header size of %d bytes exceeds the '.
                    'maximum size of %d bytes.',
                    $newHeaderSize,
                    $maxHeaderSize
                )
            );
        }

        foreach ($this->contentHeaders as $i => $headerPart) {
            $isFirstEntry = ($i === 0);
            $response->setHeader($headerName, $headerPart.';', $isFirstEntry);
        }
    }

    private function getHeaderName(HttpResponse $response): string
    {
        $headerName = '';
        foreach (PolicyHeader::HEADER_NAMES as $name) {
            $headerContent = $response->getHeader($name);
            if ($headerContent) {
                $headerName = $name;
            }
        }
        return $headerName;
    }
}
