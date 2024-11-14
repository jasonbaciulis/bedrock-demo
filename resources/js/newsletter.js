document.addEventListener('alpine:init', () => {
  Alpine.data('newsletter', ({ route, siteName }) => {
    return {
      error: false,
      errors: [],
      sending: false,
      success: false,
      subscribed: false,
      email: '',
      storageKey: `${siteName}_newsletter_subscribed`,

      isSubscribed() {
        const subscribed = localStorage.getItem(this.storageKey)
        this.subscribed = !!subscribed
      },

      setFormState({ success, error, errors = [] }) {
        this.success = success
        this.error = error
        this.errors = errors
      },

      handleSuccess() {
        this.setFormState({ success: true, error: false })
        localStorage.setItem(this.storageKey, true)
        this.$refs.form.reset()
      },

      async submit() {
        try {
          // If honeypot field is filled by bots show "successful" submission.
          if (this.$refs.honeypot.value) {
            this.setFormState({ success: true, error: false })
            this.$refs.form.reset()

            return
          }

          this.sending = true

          const response = await fetch(route, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
            method: 'POST',
            body: new FormData(this.$refs.form),
          })
          const json = await response.json()

          if (json['success']) {
            this.handleSuccess()
          } else {
            this.setFormState({ success: false, error: true, errors: json['errors'] })
          }
        } catch (error) {
          console.error(error)
        } finally {
          this.sending = false
        }
      },
    }
  })
})
