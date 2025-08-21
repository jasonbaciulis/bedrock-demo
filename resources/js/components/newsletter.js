document.addEventListener('alpine:init', () => {
  const persistKey = appName.toLowerCase().replaceAll(' ', '_') + '_newsletter_subscribed'

  Alpine.data('newsletter', ({ route }) => {
    return {
      error: false,
      errors: [],
      success: false,
      subscribed: Alpine.$persist(false).as(persistKey),
      form: {
        email: '',
        honeypot: '',
        processing: false,
        validating: false,

        validate(fieldHandle) {
          if (typeof fieldHandle !== 'string' || !fieldHandle) return

          const value = this.form[fieldHandle]

          if (value.length > 0 && this.form.errors?.[fieldHandle]) {
            this.form.forgeError(fieldHandle)
          }
        },

        invalid(fieldHandle) {
          const map = this.errors || {}
          if (!fieldHandle) return Object.keys(map).length > 0
          if (map[fieldHandle]) return true
          return false
        },

        hasErrors() {
          return Object.keys(this.errors || {}).length > 0
        },

        forgeError(fieldHandle) {
          const next = { ...(this.form.errors || {}) }
          delete next[fieldHandle]
          this.form.errors = next
        },
      },

      get isSubscribed() {
        return this.subscribed
      },

      set isSubscribed(status) {
        this.subscribed = status
      },

      reset() {
        this.$refs.form.reset()
      },

      async fetchData(url, options) {
        const response = await fetch(url, options)
        if (!response.ok) {
          throw new Error('Newsletter subscription network error')
        }
        return response.json()
      },

      setFormState({ success, error, errors = [] }) {
        this.success = success
        this.error = error
        this.errors = errors
      },

      handleSuccess() {
        this.setFormState({ success: true, error: false })
        this.isSubscribed = true
        this.reset()
      },

      async submit() {
        try {
          // If honeypot field is filled by bots show "successful" submission.
          if (this.form.honeypot) {
            this.setFormState({ success: true, error: false })
            this.reset()

            return
          }

          this.form.processing = true

          const json = await this.fetchData(route, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
            method: 'POST',
            body: new FormData(this.$refs.form),
          })

          if (json['success']) {
            this.handleSuccess()
          } else {
            this.setFormState({ success: false, error: true, errors: json['errors'] })
          }
        } catch (error) {
          console.error(error)
        } finally {
          this.form.processing = false
        }
      },
    }
  })
})
