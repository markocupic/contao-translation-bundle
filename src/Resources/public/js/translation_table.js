const TranslationTableApp = {
  compilerOptions: {
    delimiters: ['${', '}'],
    comments: true
  },

  data: function data() {
    return {
      app: null,
      authToken: null,
      projectId: null,
      resourceId: null,
      language: null,
      rows: {},
      itemsOpened: {},
    }
  },

  mounted: function mounted() {
    this.authToken = ContaoTranslator.authToken;
    this.app = this.$refs.app;
    this.projectId = this.app.dataset.project;
    this.resourceId = this.app.dataset.resource;
    this.language = this.app.dataset.language;
    this.csrfToken = this.app.dataset.csrfToken;

    // Load items
    this.loadTable();
  },

  methods: {

    loadTable: function loadTable() {
      let self = this;

      var data = new FormData();
      data.append('authToken', self.authToken);

      fetch('/trans_api/translation_table/get_rows/' + this.resourceId + '/' + this.language, {

          method: 'POST',
          headers: {
            'x-requested-with': 'XMLHttpRequest'
          },
          body: data,
        }
      ).then(function (response) {
        return response.json();
      }).then(function (json) {
        self.rows = json.data.rows;
      });
    },

    edit: function edit(index) {
      let self = this;

      // Autosave when user switches to another item
      let open = document.querySelector('.translation-item.open');
      if (open) {
        let ind = open.dataset['index'];
        this.save(ind, false);
        setTimeout(() => this.edit(index), 50);
        return;
      }

      (function () {
        return self.open(index);
      })().then(function () {
        let input = self.app.querySelector('[data-index="' + index + '"] input');
        input.value = '';
        let sourceId = input.dataset.sourceId;

        var data = new FormData();
        data.append('sourceId', sourceId);
        data.append('REQUEST_TOKEN', this.csrfToken);
        data.append('authToken', self.authToken);

        fetch('/trans_api/translation_table/get_target_source_value/' + self.resourceId + '/' + self.language, {
          method: 'POST',
          headers: {
            'x-requested-with': 'XMLHttpRequest'
          },
          body: data
        }).then(function (response) {
          return response.json();
        }).then(function (json) {
          if (json.status === 'success') {
            input.value = json.value;
          }
        });
      });
    },

    open: function open(index) {
      this.itemsOpened[index] = true;
      return new Promise(resolve => setTimeout(resolve, 20));
    },

    close: function close(index) {
      this.itemsOpened[index] = false;
      return new Promise(resolve => setTimeout(resolve, 20));
    },

    /**
     * Save input
     * @param event
     */
    save: async function save(index) {
      let self = this;
      this.close(index);

      let input = self.app.querySelector('[data-index="' + index + '"] input');
      let value = input.value;
      let sourceId = input.dataset.sourceId;

      var data = new FormData();
      data.append('value', value);
      data.append('sourceId', sourceId);
      data.append('REQUEST_TOKEN', this.csrfToken);
      data.append('authToken', self.authToken);

      return await fetch('/trans_api/translation_table/update_row/' + this.resourceId + '/' + this.language, {
        method: 'POST',
        headers: {
          'x-requested-with': 'XMLHttpRequest'
        },
        body: data
      }).then(function (response) {
        return response.json();
      }).then(function (json) {
        if (json.status === 'success') {
          self.loadTable();
        }
      });
    },
  }
}

if (document.getElementById('translationTableApp')) {
  Vue.createApp(TranslationTableApp).mount('#translationTableApp');
}
