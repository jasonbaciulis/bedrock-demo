import { slugify } from '../../utils/shared.js'

export default {
  title: 'Slugify',
  quick: true,
  icon: 'regular/text-small',
  run: ({ value, update, store, storeName }) => {
    const values = store.state.publish[storeName].values
    update(slugify(values.title))
  },
}
