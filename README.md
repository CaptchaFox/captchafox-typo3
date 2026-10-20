# CaptchaFox for TYPO3

Official [CaptchaFox](https://captchafox.com) integration for TYPO3. It renders the CaptchaFox widget in
forms of the TYPO3 form framework (EXT:form) and verifies every answer on the server.

## Compatibility

| TYPO3 | Extension | PHP |
|---|---|---|
| 14.3 or later | 14.x (branch `main`) | 8.2 or later |
| 12.4 LTS and 13.4 LTS | **12.x (this branch)** | 8.1 or later |
| 10.4 LTS from 10.4.11 and 11.5 LTS from 11.5.7 | 10.x (branch `v10`, security fixes only) | 7.4 or later |

Other TYPO3 versions are not supported; the version constraints of the extension do not allow them.

## Installation

```bash
composer require captchafox/captchafox-typo3:^12
```

Without Composer, install the extension from the TYPO3 Extension Repository (TER). No static
template needs to be included: the extension registers its form configuration for all sites. (Up
to 12.0.1 the static template "CaptchaFox-Typo3" was required; an existing include does no harm.)

Partials and code written against 12.0.x: see [MIGRATION.md](MIGRATION.md).

## Configuration

### Get your keys

In the [CaptchaFox portal](https://portal.captchafox.com), open your website under **Sites > Site
Details** and copy the **site key** and the **secret key**.

### Set up the extension

Enter the keys (`site_key`, `secret_key`) in **Admin Tools > Settings > Extension Configuration >
captchafox_official**. Until you do, the extension uses CaptchaFox's public test keys: the widget shows "For testing purposes only." and
nothing is protected. Several sites can use their own keys, see below.

Then choose the forms, see [Usage](#usage).

### Options

| Option | Default | Meaning |
|---|---|---|
| `lang` | empty | Language code of the widget (e.g. `de`). Empty: the language of the current site language |
| `apiUnavailable` | `allow` | `allow` or `block`, see [If CaptchaFox is unreachable](#if-captchafox-is-unreachable) |
| `robotMode` | off | Switches CaptchaFox off for **all** visitors; for automated tests only, never on a live site |
| `enforceCaptcha` | off | Shows and checks CaptchaFox also in the Development context |

### Several sites (multi-domain)

In an installation with several sites, each site can use its own keys. Site settings override the
extension configuration; each value that a site does not set falls back to it.

`config/sites/<site>/settings.yaml`:

```yaml
captchafox:
  siteKey: 'sk_…'
  # Keep secrets out of version control:
  secretKey: '%env(CAPTCHAFOX_SECRET_KEY_EXAMPLE_COM)%'
```

From TYPO3 13 on you can also edit both values in **Site Management > Settings** after adding the set
"CaptchaFox" (`captchafox/captchafox`) to the site. The editor shows the secret in plain text; the
`%env()%` reference above avoids that.

## Usage

The extension protects every form of the TYPO3 form framework that contains the element
"CaptchaFox". Forms of other extensions are not covered.

### Choose where CaptchaFox is used

In the form editor, add the element "CaptchaFox" to the form. Its settings offer the **Display Mode**
(Inline or Popup) and **Hide label in frontend**. Several forms with CaptchaFox on one page work
independently.

### How a form is checked

- **On the server:** Every answer is verified with CaptchaFox before the form framework accepts the
  form. The element always verifies the answer, even if its validator was removed from the form
  definition. A form without an answer, or with an answer that CaptchaFox rejects, is shown again with
  a message at the element.

## Operation

### If CaptchaFox is unreachable

When the server gets no usable answer from CaptchaFox (network error, timeout of 5 seconds, server
error), the option `apiUnavailable` decides. By default (`allow`) the form is let through and the
outage is logged, so an outage at CaptchaFox does not lock visitors out of your forms. Choose `block`
if protection matters more to you than availability. Answers that CaptchaFox rejects are always
rejected.

### Log

Outages are written to the TYPO3 log as warnings (by default `var/log/typo3_*.log`). Rejected answers
are logged at level info, which TYPO3 only writes when the log level is lowered. The secret key is
never logged.

### Visitor IP

The extension sends the visitor's IP address with the answer (`remoteIp`). Behind a reverse proxy or
load balancer, configure `$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']`; otherwise the address
of the proxy is sent instead of the visitor's.

### Content Security Policy

If your site sends a Content Security Policy, from TYPO3 or from the web server, allow CaptchaFox in
these directives. Keep your existing values such as `'self'`, which the extension's own script needs.

| Directive | Add |
|---|---|
| `script-src` | `https://*.captchafox.com blob:` |
| `connect-src` | `https://*.captchafox.com` |
| `style-src` | `https://*.captchafox.com` |
| `img-src` | `https://*.captchafox.com` |
| `media-src` | `https://*.captchafox.com` |

- If you set `worker-src` or `child-src`, add `blob:` there as well. The widget runs a web worker from
  a `blob:` URL.
- The widget script is loaded from `https://cdn.captchafox.com/api.js`. It cannot be bundled or
  self-hosted, and Subresource Integrity (SRI) is not possible because its URL is not versioned.

### Privacy

CaptchaFox is operated by Scoria Labs GmbH, Germany. How CaptchaFox processes data and how to get a
data processing agreement is described in the [CaptchaFox Privacy Center](https://captchafox.com/privacy).
A suggested paragraph for your privacy policy is on the
[GDPR page](https://captchafox.com/privacy/gdpr).

## Troubleshooting

### The widget shows "For testing purposes only."

The extension still uses the public test keys. Enter your keys, see
[Set up the extension](#set-up-the-extension).

### "CAPTCHA was not solved"

The form was sent without an answer. Solve the widget, then send the form.

### "The CAPTCHA has expired."

The answer was too old or had already been used. Solve the widget again.

### "The CAPTCHA could not be verified for your connection. Please solve it again."

CaptchaFox could not match the answer to the visitor's connection. If the site runs behind a reverse
proxy, see [Visitor IP](#visitor-ip).

### "The CAPTCHA could not be verified right now. Please try again later."

The server got no usable answer from CaptchaFox, and `apiUnavailable` is set to `block` (the default
lets the form through). Check that the server can open HTTPS connections to `api.captchafox.com`. If
your server needs a proxy, set it in `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']`; the extension
uses it. Details are in the TYPO3 log.

### "The CAPTCHA secret parameter is invalid or malformed."

The secret key is wrong. Copy it again from the CaptchaFox portal.

### "CaptchaFox is switched off (robot mode). This form is not protected."

`robotMode` is on. Switch it off on a live site.

### "CaptchaFox is neither shown nor checked because TYPO3 runs in the Development context."

Expected in the Development context. To test CaptchaFox there, switch on `enforceCaptcha`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
