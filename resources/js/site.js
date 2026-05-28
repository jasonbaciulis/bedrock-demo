import Alpine from 'alpinejs'
import Collapse from '@alpinejs/collapse'
import Persist from '@alpinejs/persist'
import Focus from '@alpinejs/focus'
import Intersect from '@alpinejs/intersect'
import Mask from '@alpinejs/mask'
import Cookies from 'js-cookie'

window.Cookies = Cookies
window.Alpine = Alpine

Alpine.plugin([Collapse, Focus, Persist, Intersect, Mask])
Alpine.start()
