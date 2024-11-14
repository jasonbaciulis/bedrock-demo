document.addEventListener('alpine:init', () => {
  Alpine.data('newsletter', ({ route, siteName }) => {
    return {
      error: false,
      errors: [],
      sending: false,
      success: false,
      subscribed: false,
      email: '',

      isSubscribed() {
        const subscribed = localStorage.getItem(`${siteName}_newsletter_subscribed`)
        this.subscribed = !!subscribed
      },

      async submit() {
        try {
          // If hidden field is filled by bots show "successful" submission.
          if (this.$refs.honeypot.value) {
            this.errors = []
            this.success = true
            this.error = false
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
            this.errors = []
            this.success = true
            this.error = false

            localStorage.setItem(`${siteName}_newsletter_subscribed`, true)

            this.$refs.form.reset()
          } else {
            this.error = true
            this.success = false
            this.errors = json['errors']
          }
        } catch (error) {
          console.error(err)
        } finally {
          this.sending = false
        }
      },
    }
  })
})
