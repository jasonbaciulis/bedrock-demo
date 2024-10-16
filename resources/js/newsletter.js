document.addEventListener('alpine:init', () => {
  Alpine.data('newsletter', ({ route }) => {
    return {
      error: false,
      errorMessage: null,
      loading: false,
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
        const subscribed = localStorage.getItem('newsletter_subscribed')
        this.subscribed = !!subscribed
      },

      async sendForm() {
        // If hidden field is filled by bots show "successful" submission.
        if (this.$refs.honeypot.value) {
          this.errorMessage = null
          this.success = true
          this.error = false
          this.$el.reset()

          return
        }

        if (!this.email) {
          this.error = true
          this.runShake()
          return
        }

        this.loading = true

        fetch(route, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
          method: 'POST',
          body: new FormData(this.$refs.form),
        })
          .then(res => res.json())
          .then(json => {
            this.loading = false

            if (json['result'] === 'success') {
              this.errorMessage = null
              this.success = true
              this.error = false

              localStorage.setItem('newsletter_subscribed', true)

              this.$el.reset()
            } else {
              this.error = true
              this.success = false
              this.errorMessage = json['message']
              this.runShake()
            }
          })
          .catch(error => {
            this.loading = false
            this.runShake()
          })
      },
    }
  })
})
