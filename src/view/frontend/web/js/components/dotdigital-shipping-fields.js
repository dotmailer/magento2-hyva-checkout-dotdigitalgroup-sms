/**
 * This function initializes the shipping form validation.
 *
 * @param {Component} wire - The Livewire component instance.
 * @returns {Object} - Returns an object with the methods and properties.
 */
const DotdigitalShippingFormFields = (wire) => {

    return Object.assign({},
        {
            isValid: wire.entangle('isValid'),
            addressId: wire.entangle('addressId').defer,
            phoneNumber: wire.entangle('phoneNumber').defer,
        },
        {
            addressIdChangeEvent() {
                return this.addressId = this.$el.value
            },
            showFields() {
                return !this.isValid && this.addressId
            },
            inputChangeEvent(event) {
                return this.phoneNumber = this.$el.value
            },
            isShippingPhoneDisabled() {
                return !this.isValid
            },
            hideShippingFieldSet() {
                return 'display: none'
            },
        })
}

/**
 * Register the component on Alpine.js directly on the alpine:init event.
 *
 * @returns {DotdigitalShippingFormFields}
 */
export function DotdigitalShippingFormFieldsComponent() {
    /**
     * Register the component on Alpine.js directly on the alpine:init event.
     *
     * @var {Component} this.$wire - The Livewire component instance.
     */
    return DotdigitalShippingFormFields(this.$wire)
}


