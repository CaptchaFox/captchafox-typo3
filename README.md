# CaptchaFox TYPO3 Extension

CaptchaFox offers CAPTCHA protection for TYPO3 forms. This extension integrates the CaptchaFox service into the TYPO3 form framework. After installation a new form element and validator become available inside the form editor, allowing editors to easily protect forms against bots.

## Compatibility

| Extension version | Supported TYPO3 versions |
|-------------------|-------------------------|
| **10.x**          | TYPO3 v10 and v11 |
| **12.x**          | TYPO3 v12 and v13 |

## Installation

Install via Composer and choose the appropriate version for your TYPO3 installation:

```bash
composer require captchafox/captchafox-typo3:^12   # TYPO3 12 or 13
# or
composer require captchafox/captchafox-typo3:^10   # TYPO3 10 or 11
```

Activate the extension in the Extension Manager or via the TYPO3 CLI.

## Configuration

The extension can be configured through the Extension Manager. Important options defined in `ext_conf_template.txt` include your site key, secret key and language:

```txt
site_key = sk_11111111000000001111111100000000
secret_key = ok_11111111000000001111111100000000
lang = de
```

Additional options like `robotMode` and `enforceCaptcha` control whether the CAPTCHA should be shown during automated testing or development environments.

## Usage

After installation you can add the "CaptchaFox" element to a form using the form editor. Internally the partial `Resources/Private/Partials/CaptchaFox.html` renders the widget and inserts the required scripts:

```html
<div class="captchafox"
     data-sitekey="{configuration.site_key}"
     data-mode="{element.renderingOptions.displayMode}"
     data-lang="{configuration.lang}"
     data-callback="cfVerifyAndSubmit"></div>
```

Validation is handled by the service in `Classes/Services/CaptchaService.php`, which sends the user response to the configured verification server.

## License

This extension is released under the GPL-2.0-or-later license.