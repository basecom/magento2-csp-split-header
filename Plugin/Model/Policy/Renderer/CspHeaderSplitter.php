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
use Magento\Csp\Model\CspRenderer;
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
        CspRenderer $subject,
        $result,
        HttpResponse $response
    ): void {
        $headerName = $this->getHeaderName($response);
        $header = $response->getHeader($headerName);
        if (!$header instanceof HeaderInterface) {
            return;
        }

        $headerValue = $header->getFieldValue();
        $isHeaderSplittingEnabled = $this->config->isHeaderSplittingEnabled();

        $maxHeaderSize = $this->config->getMaxHeaderSize();
        $currentHeaderSize = strlen($headerValue);

        if ($isHeaderSplittingEnabled) {
            $this->registerCspHeaderPlugins($response);
            $this->splitUpCspHeaders($response, $headerName, $headerValue);
        } else {
            if ($maxHeaderSize >= $currentHeaderSize) {
                $response->setHeader($headerName, $headerValue, true);
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
    private function splitUpCspHeaders(HttpResponse $response, string $headerName, string $headerValue): void
    {
        $maxHeaderSize = $this->config->getMaxHeaderSize();

        $headerParts[$i = 0] = '';

        $policyValues = explode(';', $headerValue);
        foreach ($policyValues as $policyValue) {
            $policyValue = trim($policyValue) . ';';
            $newHeaderSize = strlen($headerParts[$i]) + strlen($policyValue);

            if ($newHeaderSize <= $maxHeaderSize) {
                $headerParts[$i] .= $policyValue;

                continue;
            }

            $headerParts[++$i] = $policyValue;
            $headerSize = strlen($policyValue);
            if ($headerSize > $maxHeaderSize) {
                $this->logger->error(
                    sprintf(
                        'Unable to set the CSP header. The header size of %d bytes exceeds the '.
                        'maximum size of %d bytes.',
                        $headerSize,
                        $maxHeaderSize
                    )
                );

                return;
            }
        }

        foreach ($headerParts as $i => $headerPart) {
            $response->setHeader($headerName, $headerPart.';', $i === 0);
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
