import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'

window.Alpine = Alpine
Alpine.plugin([collapse, focus, persist])
Alpine.start()
