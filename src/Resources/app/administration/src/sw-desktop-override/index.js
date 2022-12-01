Shopware.Component.override('sw-desktop', {
    methods: {
        checkRouteSettings() {
            this.$super('checkRouteSettings');
            if (this.isFramed) {
                this.noNavigation = true;
            }
        }
    },
    computed: {
        isFramed() {
            return this.$route.query.framed == 1;
        }
    }
});