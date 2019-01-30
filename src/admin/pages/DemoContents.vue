<template>
    <div class="tools-page">
        <h1 class="wp-heading-inline">{{ __( 'Dokan Demo Contents', 'dokan-lite' ) }}</h1>

        <postbox :title="__('Import', 'dokan-lite')">
            <p>{{ __( 'Import demo contents.', 'dokan-lite' ) }}</p>

            <button
                type="button"
                class="button button-primary"
                v-text="__('Upload', 'dokan-lite')"
                @click="openMediaManager"
            />
        </postbox>

        <postbox :title="__( 'Export', 'dokan-lite' )">
            <p>Export contents in JSON.</p>

            <button
                type="button"
                class="button button-primary"
                v-text="__('Export', 'dokan-lite')"
                @click="exportContents"
            />
        </postbox>
    </div>
</template>

<script>
    const Postbox = dokan_get_lib('Postbox');

    export default {
        name: 'DemoContents',

        components: {
            Postbox
        },

        data() {
            return {
                fileFrame: null
            };
        },

        methods: {
            exportContents() {
                const self = this;

                $.ajax({
                    url: dokan.ajaxurl,
                    method: 'post',
                    dataType: 'json',
                    data: {
                        _nonce: dokan.nonce,
                        action: 'dokan_export_demo_contents'
                    }
                }).done((response) => {

                }).fail((jqXHR) => {
                    self.showAjaxError(jqXHR);
                }).always(() => {

                });
            },

            openMediaManager() {
                const self = this;

                if (self.fileFrame) {
                    self.fileFrame.open();
                    return;
                }

                const fileStatesOptions = {
                    library: wp.media.query(),
                    multiple: false, // set it true for multiple image
                    title: __('Add Zip'),
                    priority: 20,
                    filterable: 'uploaded'
                };

                const fileStates = [
                    new wp.media.controller.Library(fileStatesOptions)
                ];

                const mediaOptions = {
                    title: __('Add Zip'),
                    library: {
                        type: 'application/zip'
                    },
                    button: {
                        text: __('Add Zip')
                    },
                    multiple: false
                };

                mediaOptions.states = fileStates;

                self.fileFrame = wp.media(mediaOptions);

                self.fileFrame.on('select', () => {
                    const selection = self.fileFrame.state().get('selection');

                    const files = selection.map((attachment) => {
                        return attachment.toJSON();
                    });

                    const file = files.pop();

                    if (file.mime !== 'application/zip') {
                        return self.alertError(__('You must select a zip contains Dokan Demo Data.', 'dokan-lite'));
                    }

                   self.importContents(file.id)
                });

                self.fileFrame.on('ready', () => {
                    self.fileFrame.uploader.options.uploader.params = {
                        type: 'dokan-demo-contents-zip-uploader'
                    };
                });

                self.fileFrame.open();
            },

            importContents(id) {
                $.ajax({
                    url: dokan.ajaxurl,
                    method: 'post',
                    dataType: 'json',
                    data: {
                        _nonce: dokan.nonce,
                        action: 'dokan_import_demo_contents',
                        id
                    }
                }).done((response) => {

                }).fail((jqXHR) => {
                    self.showAjaxError(jqXHR);
                }).always(() => {

                });
            },
        }
    };
</script>
