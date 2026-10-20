# Migration

## Own partials and integrations of 12.0.x

Partials and code written against 12.0.x keep working throughout 12.x. These parts of 12.0.x are
deprecated; they stay in 12.x and no longer exist in 14.x:

| Deprecated (12.0.x) | Use instead |
|---|---|
| Partial variables `{configuration}`, `{showCaptcha}`, `{name}` | `{captchafox.siteKey}`, `{captchafox.language}`, `{captchafox.scriptUrl}`, `{captchafox.disabledReason}` (copy the partial of the current version) |
| `CaptchaService::getConfiguration()`, `getShowCaptcha()` | `getSiteKey()`, `getWidgetLanguage()`, `getScriptUrl()`, `getDisabledReason()` |
| `CaptchaService::validate($token)` without a request | `validate($token, $request)` |
| JavaScript callback `cfVerifyAndSubmit` (`data-callback`, `data-captchafox-form-field`) | the current partial: `form.js` renders the widget and fills the field itself |

`{configuration.site_key}` ignores the site settings; only the variables of the current partial use them.

Changed in 12.1.0: an empty `lang`, now the default, follows the language of the site language; 12.0.x
defaulted to `de`. Set `lang = de` to keep the former behaviour.
