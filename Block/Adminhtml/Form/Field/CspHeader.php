<?php declare(strict_types=1);

namespace Basecom\CspSplitHeader\Block\Adminhtml\Form\Field;

use Basecom\CspSplitHeader\Api\Data\ConfigInterface;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class CspHeader extends Template implements RendererInterface
{
    private string $header = '';

    public function __construct(
        private readonly PolicyCollectorInterface $policyCollector,
        private readonly ConfigInterface $config,
        Context $context,
        array $data = [],
    ) {
        $this->_template = 'Basecom_CspSplitHeader::cspHeader.phtml';
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $this->setData('html_id', $element->getHtmlId());
        $this->setData('label', $element->getData('label'));

        return $this->_toHtml();
    }

    public function getCurrentHeaderSize(): int
    {
        return strlen($this->getCspHeader());
    }

    public function getCspHeader(): string
    {
        if (empty($this->header)) {
            $cspHeader = '';
            $policies = $this->policyCollector->collect();

            foreach ($policies as $policy) {
                $value = $policy->getValue();
                $cspHeader .= $policy->getId().': '.$value.';'.PHP_EOL;
            }
            $this->header = $cspHeader;
        }
        return $this->header ;
    }

    public function isHeaderIsTooBig(): bool
    {
        $header = $this->getCspHeader();
        $currentHeaderSize = strlen($header);
        $maxHeaderSize = $this->config->getMaxHeaderSize();

        $isHeaderSplittingEnabled = $this->config->isHeaderSplittingEnabled();
        $headerIsTooBig = false;
        if ($isHeaderSplittingEnabled) {
            $headerParts = explode(PHP_EOL, $header);
            foreach ($headerParts as $headerPart) {
                $headerPartsSize = strlen($headerPart);
                if ($headerPartsSize > $maxHeaderSize) {
                    $headerIsTooBig = true;
                    break;
                }
            }
        } else {
            $headerIsTooBig = $currentHeaderSize > $maxHeaderSize;
        }
        return $headerIsTooBig;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}
