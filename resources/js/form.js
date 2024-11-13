document.addEventListener('alpine:init', () => {
  Alpine.data('form', ({ handle }) => {
    return {
      error: false,
      errors: [],
      sending: false,
      success: false,

      submit() {
        this.runFetch(this.$refs.form.action, this[handle], this.onSuccessfulSubmission)
      },

      onSuccessfulSubmission(jsonResponse, _this) {
        // Do something with the response
        // _this.someFunction(jsonResponse)
      },

      async runFetch(route, data, successHandler) {
        this.sending = true

        try {
          const response = await fetch(route, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': this.$refs.form._token.value,
            },
            method: 'POST',
            body: JSON.stringify(data),
          })

          const json = await response.json()

          if (json['success']) {
            this.errors = []
            this.success = true
            this.error = false
            this.$refs.form.reset()

            successHandler(json, this)
          }

          if (json['error']) {
            this.error = true
            this.success = false
            this.errors = json['error']
          }
        } catch (err) {
          console.error(err)
        } finally {
          this.sending = false
        }
      },

      hasError(name) {
        return this.errors.hasOwnProperty(name)
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
