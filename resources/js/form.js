document.addEventListener('alpine:init', () => {
  const skeletonForm = document.querySelector('.js-skeleton-form')

  if (skeletonForm) {
    skeletonForm.remove()
  }

  Alpine.data('form', () => {
    return {
      error: false,
      errors: [],
      sending: false,
      success: false,

      async sendForm() {
        this.sending = true

        // Post the form.
        fetch(this.$refs.form.action, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
          method: 'POST',
          body: new FormData(this.$refs.form),
        })
          .then(res => res.json())
          .then(json => {
            if (json['success']) {
              this.errors = []
              this.success = true
              this.error = false
              this.sending = false
              this.$refs.form.reset()

              setTimeout(function () {
                this.success = false
              }, 4500)
            }
            if (json['error']) {
              this.sending = false
              this.error = true
              this.success = false
              this.errors = json['error']
            }
          })
          .catch(err => {
            err.text().then(errorMessage => {
              this.sending = false
            })
          })
      },

      forgetError(name) {
        const newErrors = { ...this.errors }
        delete newErrors[name]
        this.errors = newErrors
      },

      setError(object) {
        const newErrors = { ...this.errors }
        Object.keys(object).forEach(key => {
          newErrors[key] = object[key]
        })
        this.errors = newErrors
      },
    }
  })
})
