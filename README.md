# Basecom_CspSplitHeader Magento 2 Module

<div align="center">

[![Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Supported Magento Versions][ico-compatibility]

</div>

---

> [!IMPORTANT]  
> As of Magento 2.4.7 it is no longer possible to deactivate the Magento CSP module.

With a growing _Content Security Policies_ (CSP) whitelist, the problem can arise that the
headers `Content-Security-Policy-Report-Only` and/or `Content-Security-Policy` become so large that they exceed the
maximum permitted size of a header field, causing the web server to not process the response any further.

The CSP mechanism allows multiple policies to be specified for a resource, including via the `Content-Security-Policy`
header, the `Content-Security-Policy-Report-Only` header and a `meta`
element [[MDN](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy#multiple_content_security_policies)].
__Therefore, the headers can be specified more than once.__

This is where the module comes into play. It implements an _after method plugin_ for the
method `Magento\Csp\Model\Policy\Renderer\SimplePolicyHeaderRenderer::render`, which replaces the existing CSP headers
via the method `\Magento\Framework\App\Response\HttpInterface::setHeader`. The header is read, split so that the syntax
remains valid, and replaced by the new headers. The result is a separate header for each directive, each of which should
no longer exceed the maximum permitted length of the web server.

> [!TIP]
> If the headers are too large even after splitting, try to identify unnecessary Magento modules and remove them.

## Installation

1. Install it into your Magento 2 project with composer:

    ```console
    composer require basecom/magento2-csp-split-header
    ```

2. Enable module

    ```console
    bin/magento setup:upgrade
    ```

## Configuration

| Config                                                      | Default Value  | Description                                                |
|-------------------------------------------------------------|----------------|------------------------------------------------------------|
| `basecom_csp_split_header/settings/header_splitting_enable` | 0 _(disabled)_ | enables (1) / disables (0) the splitting of the CSP header |
| `basecom_csp_split_header/settings/max_header_size`         | 8190           | maximum allowed header field size                          |

These values can be updated in the system configuration under `Basecom -> Content Security Policy -> Enable`.

## Example

1. CSP splitting _disabled_

    ```HTTP
    Content-Security-Policy: default-src 'self' https://example.com; connect-src 'none'; script-src https://example.com/;                          
    ```

2. CSP splitting _enabled_

    ```HTTP
    Content-Security-Policy: default-src 'self' https://example.com; 
    Content-Security-Policy: connect-src 'none'; 
    Content-Security-Policy: script-src https://example.com/;                          
    ```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email <magento@basecom.de> instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Copyright

&copy; 2024 basecom GmbH & Co. KG

[ico-version]: https://img.shields.io/packagist/v/basecom/magento2-csp-split-header.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-compatibility]: https://img.shields.io/badge/magento-2.4-brightgreen.svg?logo=magento&longCache=true&style=flat-square

[link-packagist]: https://packagist.org/packages/basecom/magento2-csp-split-header
