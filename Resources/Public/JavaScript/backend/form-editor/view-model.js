/**
 * Module: @captchafox/captchafox-typo3/backend/form-editor/view-model.js
 *
 * Renders the CaptchaFox element in the stage of the form editor (TYPO3 12 and 13).
 */
import * as Helper from '@typo3/form/backend/form-editor/helper.js';

let formEditorApp = null;

function subscribeEvents() {
  formEditorApp.getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', (topic, args) => {
    if (args[0].get('type') === 'CaptchaFox') {
      formEditorApp.getViewModel().getStage().renderSimpleTemplateWithValidators(args[0], args[1]);
    }
  });
}

export function bootstrap(app) {
  formEditorApp = app;
  formEditorApp.assert(
    typeof Helper.bootstrap === 'function',
    'The view model helper does not implement the method "bootstrap"',
    1491643380
  );
  Helper.bootstrap(formEditorApp);
  subscribeEvents();
}
