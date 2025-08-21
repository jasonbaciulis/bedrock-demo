document.addEventListener('alpine:init', () => {
  Alpine.data('form', () => {
    return {
      error: false,
      success: false,

      submit() {
        this.form
          .submit()
          .then(response => {
            console.log(response)
            this.handleSuccess()
          })
          .catch(error => {
            console.log(error)
            this.handleError()
          })
      },

      handleSuccess() {
        this.success = true
        this.error = false
        this.form.reset()
      },

      handleError() {
        this.error = true
        this.success = false
      },
    }
  })
})
