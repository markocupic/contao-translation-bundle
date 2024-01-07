document.addEventListener("DOMContentLoaded", function () {
    const vueElement = document.getElementById('translationTableApp');
    if (vueElement) {
        new TranslationTableApp(vueElement);
    }
});

class TranslationTableApp {
    constructor(vueElement, options) {

        const {createApp} = Vue

        const app = createApp({

            data() {
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
            watch: {},

            mounted() {
                this.authToken = ContaoTranslator.authToken;
                this.app = this.$refs.app;
                this.projectId = this.app.dataset.project;
                this.resourceId = this.app.dataset.resource;
                this.language = this.app.dataset.language;
                this.csrfToken = this.app.dataset.csrfToken;

                // Load items
                this.reloadData();
            },

            methods: {
                /**
                 * Load rows from server
                 * @returns {Promise<any>}
                 */
                reloadData: async function reloadData() {
                    let self = this;

                    const data = new FormData();
                    data.append('authToken', self.authToken);

                    return await fetch('/trans_api/translation_table/get_rows/' + this.resourceId + '/' + this.language, {

                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data,
                    }).then(function (response) {
                        return response.json();
                    }).then(function (json) {
                        self.rows = json.data.rows;
                    });
                },

                /**
                 * User has pressed the edit button
                 * @param index
                 * @returns {Promise<any>}
                 */
                edit: async function edit(index) {
                    let self = this;

                    // Autosave previous input,
                    // if user switches directly to another item
                    let open = document.querySelector('.translation-item.open');
                    if (open) {
                        let i = open.dataset['index'];
                        await this.save(i);
                        await this.edit(index);
                        return;
                    }

                    // Get the source id
                    let row = self.app.querySelector('[data-index="' + index + '"]');
                    let sourceId = row.dataset.sourceId;

                    const data = new FormData();
                    data.append('sourceId', sourceId);
                    data.append('REQUEST_TOKEN', this.csrfToken);
                    data.append('authToken', self.authToken);

                    return await fetch('/trans_api/translation_table/get_target_source_value/' + self.resourceId + '/' + self.language, {
                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data
                    }).then(function (response) {
                        return response.json();
                    }).then(async function (json) {
                        if (json.status === 'success') {
                            await self.open(index);
                            let input = row.querySelector('input');
                            input.value = json.value;
                        }
                    });
                },

                /**
                 * Open the input field of the selected row
                 * @param index
                 * @returns {Promise<unknown>}
                 */
                open: function open(index) {
                    this.itemsOpened[index] = true;
                    return new Promise(resolve => setTimeout(resolve, 20));
                },

                /**
                 * Close the input field of the selected row
                 * @param index
                 * @returns {Promise<unknown>}
                 */
                close: function close(index) {
                    this.itemsOpened[index] = false;
                    return new Promise(resolve => setTimeout(resolve, 20));
                },

                /**
                 * Save input
                 * @param index
                 * @returns {Promise<any>}
                 */
                save: async function save(index) {
                    let self = this;

                    // Fetch value before closing the form input[type="text"]
                    let row = self.app.querySelector('[data-index="' + index + '"]');
                    let sourceId = row.dataset.sourceId;
                    let input = row.querySelector('input');

                    // Close form input
                    await this.close(index);

                    const data = new FormData();
                    data.append('value', input.value);
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
                            self.reloadData();
                        }
                    });
                },

            },
        });

        app.config.compilerOptions.delimiters = ['${', '}'];
        app.comments = true;
        return app.mount(vueElement);

    }
}
