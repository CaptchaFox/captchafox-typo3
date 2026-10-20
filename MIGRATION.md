# Migration

## Own partials and integrations of 10.0.x

Partials and code written against 10.0.x keep working throughout 10.x. These parts of 10.0.x are
deprecated; 12.x keeps them as well, 14.x no longer has them:

| Deprecated (10.0.x) | Use instead |
|---|---|
| Partial variables `{configuration}`, `{showCaptcha}`, `{name}` | `{captchafox.siteKey}`, `{captchafox.language}`, `{captchafox.scriptUrl}`, `{captchafox.disabledReason}` (copy the partial of the current version) |
| `CaptchaService::getConfiguration()`, `getShowCaptcha()` | `getSiteKey()`, `getWidgetLanguage()`, `getScriptUrl()`, `getDisabledReason()` |
| `CaptchaService::validate($token)` without a request | `validate($token, $request)` |
| JavaScript callback `cfVerifyAndSubmit` (`data-callback`, `data-captchafox-form-field`) | the current partial: `form.js` renders the widget and fills the field itself |
