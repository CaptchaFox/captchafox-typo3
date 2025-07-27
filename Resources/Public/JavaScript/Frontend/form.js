function cfVerifyAndSubmit(response) {
    if (response) {
        const target = document.querySelector('[data-captchafox-form-field]')
        if (target) {
            target.value = response
        }
    }
}

window.cfVerifyAndSubmit = cfVerifyAndSubmit;