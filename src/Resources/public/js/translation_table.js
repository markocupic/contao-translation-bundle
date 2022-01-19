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
      rows: null,
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

    /**
     * Open form
     * @param row
     */
    edit: function edit(index) {
      let self = this;

      // Autosave when user switches to another item
      let open = document.querySelector('.translation-item.open');
      if(open)
      {
        let index = open.dataset.index;
        this.save(index);
      }

      this.closeAll();
      (function () {
        self.rows[index].isOpen = true;
        return new Promise(resolve => setTimeout(resolve, 100));
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

    /**
     * Save input
     * @param event
     */
    save: function save(index) {
      let self = this;

      let input = self.app.querySelector('[data-index="' + index + '"] input');
      let value = input.value;
      let sourceId = input.dataset.sourceId;

      var data = new FormData();
      data.append('value', value);
      data.append('sourceId', sourceId);
      data.append('REQUEST_TOKEN', this.csrfToken);
      data.append('authToken', self.authToken);

      fetch('/trans_api/translation_table/update_row/' + this.resourceId + '/' + this.language, {
        method: 'POST',
        headers: {
          'x-requested-with': 'XMLHttpRequest'
        },
        body: data
      }).then(function (response) {
        return response.json();
      }).then(function (json) {
        if (json.status === 'success') {
          self.closeAll();
          self.loadTable();
        }
      });
    },

    /**
     * close all
     */
    closeAll: function closeAll() {
      for (let i = 0; i < this.rows.length; i++) {
        this.rows[i].isOpen = false;
      }
    },
  }
}

if (document.getElementById('translationTableApp')) {
  Vue.createApp(TranslationTableApp).mount('#translationTableApp');
}
