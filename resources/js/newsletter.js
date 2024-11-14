document.addEventListener('alpine:init', () => {
  Alpine.data('newsletter', ({ route, siteName }) => {
    return {
      error: false,
      errors: [],
      sending: false,
      success: false,
      subscribed: false,
      email: '',
      shake: false,

      runShake() {
        this.shake = true

        setTimeout(() => {
          this.shake = false
        }, 200)
      },

      isSubscribed() {
        const subscribed = localStorage.getItem(`${siteName}_newsletter_subscribed`)
        this.subscribed = !!subscribed
      },

      async sendForm() {
        // If hidden field is filled by bots show "successful" submission.
        if (this.$refs.honeypot.value) {
          this.errors = []
          this.success = true
          this.error = false
          this.$el.reset()

          return
        }

        this.sending = true

        fetch(route, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
          method: 'POST',
          body: new FormData(this.$refs.form),
        })
          .then(res => res.json())
          .then(json => {
            this.sending = false

            if (json['success']) {
              this.errors = []
              this.success = true
              this.error = false

              localStorage.setItem(`${siteName}_newsletter_subscribed`, true)

              this.$refs.form.reset()
            } else {
              this.error = true
              this.success = false
              this.errors = json['error']
              this.runShake()
            }
          })
          .catch(error => {
            this.sending = false
            this.runShake()
          })
      },
    }
  })
})
