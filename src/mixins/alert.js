export default {
    methods: {
        // When working with REST API, we should have another
        // method called showResponseError or showRestError :: Edi Amin
        showAjaxError(jqXHR) {
            if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                this.$notify({
                    title: __('Error', 'dokan-lite'),
                    text: jqXHR.responseJSON.data.message,
                    type: 'error'
                });
            }
        },

        alertError(message, title) {
            this.$notify({
                title: title || __('Error', 'dokan-lite'),
                text: message,
                type: 'error'
            });
        }
    }
};
