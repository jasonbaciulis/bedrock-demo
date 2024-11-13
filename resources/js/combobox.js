document.addEventListener('alpine:init', () => {
  Alpine.data('combobox', config => {
    return {
      items: config.items,
      itemsFiltered: [],
      itemActive: null,
      itemSelected: null,
      id: config.id || 'combobox',
      comboboxSearch: '',
      listboxOpen: false,
      emptyOptionsMessage: config.emptyOptionsMessage || 'No results match your search.',

      init() {
        if (typeof this.items === 'object' && this.items !== null && !Array.isArray(this.items)) {
          this.convertItemsToArray()
        }

        this.searchItems()
        this.$watch('comboboxSearch', value => this.searchItems())
      },

      convertItemsToArray() {
        this.items = Object.entries(this.items).map(([key, value]) => ({ key, value }))
      },

      searchIsEmpty() {
        return this.comboboxSearch.length == 0
      },

      itemIsActive(item) {
        return this.itemActive && this.itemActive.key == item.key
      },

      itemIsSelected(item) {
        return this.itemSelected && this.itemSelected.key == item.key
      },

      itemActiveNext() {
        let index = this.itemsFiltered.indexOf(this.itemActive)
        if (index < this.itemsFiltered.length - 1) {
          this.itemActive = this.itemsFiltered[index + 1]
          this.scrollToActiveItem()
        }
      },

      itemActivePrevious() {
        let index = this.itemsFiltered.indexOf(this.itemActive)
        if (index > 0) {
          this.itemActive = this.itemsFiltered[index - 1]
          this.scrollToActiveItem()
        }
      },

      scrollToActiveItem() {
        let activeElement
        let newScrollPos
        if (this.itemActive) {
          activeElement = document.getElementById(this.itemActive.key + '-' + this.id)
          if (!activeElement) return

          newScrollPos =
            activeElement.offsetTop + activeElement.offsetHeight - this.$refs.listbox.offsetHeight
          if (newScrollPos > 0) {
            this.$refs.listbox.scrollTop = newScrollPos
          } else {
            this.$refs.listbox.scrollTop = 0
          }
        }
      },

      searchItems() {
        if (!this.searchIsEmpty()) {
          const searchTerm = this.comboboxSearch.replace(/\*/g, '').toLowerCase()
          this.itemsFiltered = this.items.filter(item =>
            item.value.toLowerCase().includes(searchTerm)
          )

          this.scrollToActiveItem()
        } else {
          this.itemsFiltered = this.items
        }
        this.itemActive = this.itemsFiltered[0]
        this.$dispatch('combobox-input', this.itemActive)
      },

      closeListbox() {
        this.itemSelected = this.itemActive
        this.comboboxSearch = this.itemSelected.value
        this.listboxOpen = false
      },

      openListbox() {
        this.listboxOpen = true

        this.$nextTick(() => {
          this.$refs.comboboxInput.focus()
          this.scrollToActiveItem()
        })
      },

      selectOption() {
        if (this.itemActive) {
          this.closeListbox()
        }
      },
    }
  })
})
