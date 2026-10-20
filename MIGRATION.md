# Migration

## Upgrading from 12.x

14.x is a new release line and drops the parts of 12.0.x that 12.x still keeps as deprecated. If your
site uses its own copy of the CaptchaFox partial or calls the service from own code, adapt it:

| Removed in 14.x | Use instead |
|---|---|
| Partial variables `{configuration}`, `{showCaptcha}`, `{name}` | `{captchafox.siteKey}`, `{captchafox.language}`, `{captchafox.scriptUrl}`, `{captchafox.disabledReason}`; start from the partial of this version |
| JavaScript callback `cfVerifyAndSubmit` (`data-callback`, `data-captchafox-form-field` filled by it) | the partial of this version: `form.js` renders the widget and fills the field itself |
| `CaptchaService::getConfiguration()`, `getShowCaptcha()` | `getSiteKey()`, `getWidgetLanguage()`, `getScriptUrl()`, `getDisabledReason()` |
| `CaptchaService::validate($token)` without a request | `validate($token, $request)`; the request is required |
| `$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['captchafox_official']['api_server']` and `['verify_server']` | `CaptchaService::SCRIPT_URL` and `CaptchaService::VERIFY_URL` |
| Form editor module `Resources/Public/JavaScript/backend/form-editor/view-model.js` | nothing; the form editor of TYPO3 14 renders the element itself |

The form configuration now comes as the form set `captchafox/captchafox-typo3`, which TYPO3 loads by itself.
An include of the former static template or of the TypoScript registration of 12.x is no longer needed.
