# CaptchaFox TYPO3 Extension

CaptchaFox protects TYPO3 forms against bots. This extension (`captchafox_official`) adds a
"CaptchaFox" element to the TYPO3 form framework (EXT:form). Editors place it in a form with the
form editor; the extension renders the CaptchaFox widget and verifies the answer on the server
before the form is accepted.

## Compatibility

| Extension version | Branch | TYPO3 | PHP |
|---|---|---|---|
| 14.x | `main` | 14.3 or newer | 8.2+ |
| **12.x (this branch)** | `v12` | 12.4 LTS and 13.4 LTS | 8.1+ |
| 10.x (no further development) | `v10` | 10.4.11+ and 11.5.7+ | 7.4+ |

## Installation

```bash
composer require captchafox/captchafox-typo3:^12
```

Without Composer, install the extension from the TYPO3 Extension Repository (TER). No static
template needs to be included: the extension registers its form configuration for all sites. (Up
to 12.0.1 the static template "CaptchaFox-Typo3" was required; an existing include does no harm.)

## Configuration

Set the keys in **Admin Tools > Settings > Extension Configuration > captchafox_official** (several
sites can override them, see below):

| Option | Default | Meaning |
|---|---|---|
| `site_key` | public test key | Site key from the CaptchaFox portal (Sites) |
| `secret_key` | public test key | Secret key of your CaptchaFox organization |
| `lang` | empty | Widget language code (e.g. `de`). Empty: the language of the current site language |
| `apiUnavailable` | `allow` | If CaptchaFox gives no usable answer (network error, timeout after 5 s, server error): `allow` lets the form through, `block` rejects it. Answers that CaptchaFox rejects are always rejected. Both cases are written to the TYPO3 log. |
| `robotMode` | off | Switches CaptchaFox off for **all** visitors, for automated tests only. Never enable it on a live site. |
| `enforceCaptcha` | off | Without it, CaptchaFox is neither shown nor checked while TYPO3 runs in the Development context. |

The default keys are CaptchaFox's public test keys: the widget shows "For testing purposes only."
and nothing is protected until you enter your own keys.

## Several sites (multi-domain)

In an installation with several sites, each site can use its own keys. Site settings override the
extension configuration; each value that a site does not set falls back to it. Usually only the site
key differs, because the secret key belongs to the CaptchaFox organization.

`config/sites/<site>/settings.yaml`:

```yaml
captchafox:
  siteKey: 'sk_…'
  # Only if this site belongs to another CaptchaFox organization. Keep secrets out of version control:
  secretKey: '%env(CAPTCHAFOX_SECRET_KEY_EXAMPLE_COM)%'
```

From TYPO3 13 on you can also edit both values in **Site Management > Settings** after adding the set
"CaptchaFox" (`captchafox/captchafox`) to the site. The editor shows the secret in plain text; the
`%env()%` reference above avoids that.

## Usage

Add the element "CaptchaFox" to a form in the form editor. The element always verifies the answer,
even if its validator was removed from the form definition. Several forms with CaptchaFox on one
page work independently.

## Notes for operators

- **Reverse proxies:** The visitor's IP address is sent to CaptchaFox (`remoteIp`). Behind a proxy
  or load balancer, configure `$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']`, otherwise the
  proxy's address is sent.
- **Content Security Policy:** Allow `https://*.captchafox.com` for `script-src` (plus `blob:`),
  `connect-src`, `style-src`, `img-src` and `media-src`.
- The widget script is loaded from `https://cdn.captchafox.com/api.js`; it cannot be bundled or
  self-hosted.

## License

GPL-2.0-or-later
