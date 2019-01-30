<template>
    <div class="tools-page">
        <h1 class="wp-heading-inline">{{ __('Tools', 'dokan-lite') }}</h1>

        <postbox v-for="tool in tools" :key="tool.action" :title="tool.title">
            <p v-text="tool.desc" />

            <div v-if="currentAction === tool.action && tool.showProgressBar">
                <progressbar :value="progressValue" />
            </div>

            <a
                class="button button-primary"
                v-text="tool.button"
                @click.prevent="doAction(tool)"
                :disabled="isAjaxWorking"
            />
        </postbox>
    </div>
</template>
<script>
    const Postbox = dokan_get_lib('Postbox');
    const Progressbar = dokan_get_lib('Progressbar');

    export default {
        name: 'Tools',

        components: {
            Postbox,
            Progressbar
        },

        data() {
            return {
                progressValue: -1,
                isAjaxWorking: false,
                currentAction: '',
                defaultTools: [
                    {
                        title: __('Page Installation', 'dokan-lite'),
                        desc: __('Clicking this button will create required pages for the plugin.', 'dokan-lite'),
                        button: __('Install Dokan Pages', 'dokan-lite'),
                        action: 'create_pages',
                        callback: this.createPages,
                    },
                    {
                        title: __('Demo Contents', 'dokan-lite'),
                        desc: __('Install demo content data.', 'dokan-lite'),
                        button: __('Go to page', 'dokan-lite'),
                        action: 'dokan_install_demo_contents',
                        callback: this.demoContents,
                    }
                ]
            }
        },

        computed: {
            tools() {
                return dokan.hooks.applyFilters('dokan_admin_tools', this.defaultTools, this);
            }
        },

        watch: {
            isAjaxWorking(working) {
                if (! working) {
                    this.progressValue = -1;
                    this.currentAction = '';
                } else if (this.progressValue < 0) {
                    this.progressValue = 0;
                }
            }
        },

        methods: {
            doAction(tool) {
                this.currentAction = tool.action;
                tool.callback.call(this, tool);
            },

            calculateProgress(total, completed) {
                const progress = (completed * 100) / total;

                if (! isNaN(progress)) {
                    this.progressValue = Math.round(progress);
                } else {
                    this.progressValue = -1;
                }
            },

            createPages(tool) {
                const self = this;
                const data = {
                    action: tool.action
                };

                self.isAjaxWorking = true;

                $.post(dokan.ajaxurl, data, function(response) {
                    if (response.success) {
                        self.$notify({
                            title: __('Success!', 'dokan-lite'),
                            text: response.data.message,
                            type: 'success'
                        });
                    } else {
                        self.$notify({
                            title: __('Failure!', 'dokan-lite'),
                            text: __('Something went wrong.'),
                            type: 'warn'
                        });
                    }

                    self.isAjaxWorking = false;
                } );
            },

            demoContents(tool) {
                this.$router.push({
                    name: 'DemoContents'
                });
            }
        }
    }
</script>
