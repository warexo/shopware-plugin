import './sw-page.scss';

Shopware.Component.override('sw-page', {
    computed: {
        pageClasses() {
            return Object.assign({}, this.$super('pageClasses'), {
                'is--framed': this.isFramed,
            });
        },
        isFramed() {
            return this.$route.query.framed == 1;
        }
    }
});