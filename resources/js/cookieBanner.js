document.addEventListener('alpine:init', () => {
  const env = process.env

  Alpine.store('cookieBanner', {
    data: Alpine.$persist(null).as(`cookieBanner${env.VITE_APP_NAME}`),

    setData(consentData) {
      this.data = consentData
    },

    acceptAll() {
      this.data.types.forEach(type => (type.value = true))
      this.saveConsent()
    },

    getConsent() {
      return this.data.consent
    },

    getConsentAPIValues() {
      return this.data.types
        .filter(type => {
          return type['consent_api'] === true
        })
        .reduce((acc, type) => {
          acc[type.consent_api_handle] = type.value ? 'granted' : 'denied'
          return acc
        }, {})
    },

    getConsentDate() {
      return this.data.date
    },

    getConsentTypes() {
      return this.data.types
    },

    invalidate(consentData) {
      this.data = consentData
    },

    rejectAll() {
      this.data.types.forEach(type => (type.value = false))
      this.saveConsent()
    },

    revokeConsent() {
      this.data.consent = false
      this.data.date = null
    },

    saveConsent() {
      this.data.consent = true
      this.data.date = Math.floor(Date.now() / 1000)
    },

    useConsentAPI() {
      return this.data.consent_api
    },
  })

  Alpine.data('cookieBanner', settings => {
    return {
      ...settings,
      data: null,
      settingsOpen: false,

      init() {
        console.log(settings.consentData)
        Alpine.store('cookieBanner').setData(settings.consentData)
        this.data = Alpine.store('cookieBanner').data

        if (Alpine.store('cookieBanner').getConsentDate() < settings.consentRevokeBefore) {
          Alpine.store('cookieBanner').invalidate(settings.consentData)
        }
        if (
          Alpine.store('cookieBanner').useConsentAPI() &&
          Alpine.store('cookieBanner').getConsent()
        ) {
          gtag('consent', 'update', Alpine.store('cookieBanner').getConsentAPIValues())
        }
        if (Alpine.store('cookieBanner').useConsentAPI()) {
          this.$watch(
            'data.consent',
            value =>
              value && gtag('consent', 'update', Alpine.store('cookieBanner').getConsentAPIValues())
          )
        }
      },
    }
  })
})
