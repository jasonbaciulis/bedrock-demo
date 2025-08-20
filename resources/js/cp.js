/**
 * When extending the control panel, be sure to uncomment the necessary code for your build process:
 * https://statamic.dev/extending/control-panel
 */

import slugifyAction from './components/actions/slugify'

Statamic.booting(() => {
  Statamic.$fieldActions.add('slug-fieldtype', slugifyAction)
})
