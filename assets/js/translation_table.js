document.addEventListener("DOMContentLoaded", () => {
    const vueElement = document.getElementById('translationTableApp');
    if (vueElement) {
        sw = Swapy;
        new TranslationTableApp(vueElement);
    }
});

class TranslationTableApp {
    constructor(vueElement, options) {

        const {createApp, ref} = Vue

        const app = createApp({
            setup() {
                return {
                    app: ref(null),
                    rows: ref({}),
                    itemsOpened: ref({}),
                    swapy: ref({}),
                    authToken: null,
                    projectId: null,
                    resourceId: null,
                    language: null,
                    modal: null,
                    container: ref(null),
                }
            },
            async mounted() {
                this.authToken = ContaoTranslator.authToken;
                this.app = this.$refs.app;

                this.projectId = this.app.dataset.project;
                this.resourceId = this.app.dataset.resource;
                this.language = this.app.dataset.language;
                this.csrfToken = this.app.dataset.csrfToken;
                this.modal = new bootstrap.Modal('#addNewModal');

                document.getElementById('addNewModal').addEventListener('show.bs.modal', event => {
                    this.$nextTick(() => {
                        event.target.querySelector('#translationId').focus();
                    });
                });

                // Load items
                await this.reloadData();
            },
            unmounted() {
                this.swapy.value.destroy();
            },

            methods: {
                /**
                 * Load rows from server
                 * @returns {Promise<any>}
                 */
                async reloadData() {

                    const data = new FormData();
                    data.append('authToken', this.authToken);

                    return await fetch('/trans_api/translation_table/get_rows/' + this.resourceId + '/' + this.language, {

                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data,
                    }).then((response) => {
                        return response.json();
                    }).then((json) => {
                        this.rows = json.data.rows;
                    }).then(async () => {
                        await (() => {
                            this.$nextTick(() => {
                                this.swapy.value = sw.createSwapy(this.container, {
                                    animation: 'dynamic',
                                    dragAxis: 'y',
                                });
                            });
                        })();
                        this.swapy.value.onSwap((event) => {
                            this.changeOrder(JSON.stringify(event.newSlotItemMap.asArray));
                        });
                    });
                },

                /**
                 *
                 * @param parentId
                 * @param language
                 */
                openModal(sourceId, translationId, sourceLanguage) {
                    const modal = document.querySelector('#addNewModal');
                    modal.querySelector('#translationId').value = translationId.split('.')[0] + '.';
                    modal.querySelector('#translationString').value = '';
                    modal.querySelector('#language').value = sourceLanguage;
                    modal.querySelector('#sourceId').value = sourceId
                    modal.querySelector('#sourceId').setAttribute('value', sourceId);
                    modal.querySelector('#modalErrorBox').textContent = '';
                    modal.querySelector('[type="submit"]').disabled = false;
                    this.modal.show();
                },

                async insertNew(event) {
                    event.preventDefault();
                    event.target.querySelector('[type="submit"]').disabled = true;
                    event.target.querySelector('#modalErrorBox').textContent = '';

                    const data = new FormData();
                    data.append('sourceId', event.target.querySelector('#sourceId').value);
                    data.append('translationId', event.target.querySelector('#translationId').value);
                    data.append('translationString', event.target.querySelector('#translationString').value);
                    data.append('language', event.target.querySelector('#language').value);
                    data.append('REQUEST_TOKEN', this.csrfToken);
                    data.append('authToken', this.authToken);

                    return await fetch('/trans_api/translation_table/insert_new_row', {
                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data
                    }).then((response) => {
                        return response.json();
                    }).then(async (json) => {
                        if (json.status === 'success') {
                            this.reloadData();
                            const modal = new bootstrap.Modal('#addNewModal');
                            event.target.querySelector('#modalErrorBox').textContent = '';
                            this.modal.hide();
                        } else {
                            event.target.querySelector('#modalErrorBox').textContent = json.message;
                            event.target.querySelector('[type="submit"]').disabled = false;
                        }
                    });
                },

                async deleteRow(sourceId) {
                    event.preventDefault();

                    const data = new FormData();
                    data.append('sourceId', sourceId);
                    data.append('REQUEST_TOKEN', this.csrfToken);
                    data.append('authToken', this.authToken);

                    return await fetch('/trans_api/translation_table/delete_row', {
                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data
                    }).then((response) => {
                        return response.json();
                    }).then(async (json) => {
                        if (json.status === 'success') {
                            this.reloadData();
                        }
                    });
                },

                async changeOrder(translationStack) {

                    const data = new FormData();
                    data.append('translationStack', translationStack);
                    data.append('REQUEST_TOKEN', this.csrfToken);
                    data.append('authToken', this.authToken);

                    return await fetch('/trans_api/translation_table/change_order', {
                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data
                    }).then((response) => {
                        return response.json();
                    }).then(async (json) => {
                        if (!json.status === 'success') {
                            log.error(json.message);
                        }
                    });
                },

                /**
                 * User has pressed the edit button
                 * @param sourceId
                 * @returns {Promise<any>}
                 */
                async edit(sourceId) {

                    // Autosave previous input,
                    // if user switches directly to another item
                    let previousOpenRow = document.querySelector('.translation-item.open');
                    if (previousOpenRow) {
                        await this.save(previousOpenRow.dataset['sourceId']);
                        await this.edit(sourceId);
                        return;
                    }

                    // Get the source id
                    let row = this.app.querySelector('[data-source-id="' + sourceId + '"]');

                    const data = new FormData();
                    data.append('sourceId', sourceId);
                    data.append('REQUEST_TOKEN', this.csrfToken);
                    data.append('authToken', this.authToken);

                    return await fetch('/trans_api/translation_table/get_target_source_value/' + this.resourceId + '/' + this.language, {
                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data
                    }).then((response) => {
                        return response.json();
                    }).then(async (json) => {
                        if (json.status === 'success') {
                            await this.open(sourceId);

                            this.$nextTick(() => {
                                const input = row.querySelector('input[name="translation"]');
                                input.value = json.value;
                            });
                        }
                    });
                },

                /**
                 * Open the input field of the selected row
                 * @param sourceId
                 * @returns {Promise<unknown>}
                 */
                open(sourceId) {
                    this.itemsOpened[sourceId] = true;
                    return new Promise(resolve => setTimeout(resolve, 20));
                },

                /**
                 * Close the input field of the selected row
                 * @param sourceId
                 * @returns {Promise<unknown>}
                 */
                close(sourceId) {
                    this.itemsOpened[sourceId] = false;
                    return new Promise(resolve => setTimeout(resolve, 20));
                },

                /**
                 * Save input
                 * @param sourceId
                 * @returns {Promise<any>}
                 */
                async save(sourceId) {

                    // Fetch value before closing the form input[type="text"]
                    let row = this.app.querySelector('[data-source-id="' + sourceId + '"]');
                    let input = row.querySelector('input');

                    // Close form input
                    await this.close(sourceId);

                    const data = new FormData();
                    data.append('value', input.value);
                    data.append('sourceId', sourceId);
                    data.append('REQUEST_TOKEN', this.csrfToken);
                    data.append('authToken', this.authToken);

                    return await fetch('/trans_api/translation_table/update_row/' + this.resourceId + '/' + this.language, {
                        method: 'POST',
                        headers: {
                            'x-requested-with': 'XMLHttpRequest'
                        },
                        body: data
                    }).then((response) => {
                        return response.json();
                    }).then((json) => {
                        if (json.status === 'success') {
                            this.reloadData();
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
