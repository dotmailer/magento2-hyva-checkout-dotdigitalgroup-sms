/**
 * This function initializes the shipping form validation.
 *
 * @param {Component} wire - The Livewire component instance.
 * @returns {Object} - Returns an object with the methods and properties.
 */
const DotdigitalMarketingConsentFormFields = (wire) => {

    return Object.assign({},
        {
            marketingConsent: false,
            marketingConsentPhoneNumber: wire.entangle('marketingConsentPhoneNumber').defer,
            marketingConsentLabel: wire.entangle('marketingConsentLabel'),
            marketingConsentText: wire.entangle('marketingConsentText')
        },
        {
            showMarketingConsent() {
                return this.marketingConsent
            },
            isMarketingConsentDisabled() {
                return !this.marketingConsent
            },
            checkboxChangeEvent(event) {
                return this.marketingConsent = event.target.checked
            },
            inputChangeEvent(event) {
                return this.marketingConsentPhoneNumber = this.$el.value
            }
        })
}

/**
 * Register the component on Alpine.js directly on the alpine:init event.
 *
 * @returns {DotdigitalMarketingConsentFormFields}
 */
export function DotdigitalMarketingConsentFormFieldsComponent() {
    /**
     * Register the component on Alpine.js directly on the alpine:init event.
     *
     * @var {Component} this.$wire - The Livewire component instance.
     */
    return DotdigitalMarketingConsentFormFields(this.$wire)
}


