/**
 * This function initializes the shipping form validation.
 *
 * @param {HTMLFormElement} form - The form element to be validated.
 * @param {Component} wire - The Livewire component instance.
 * @returns {Object} - Returns an object with the form validation methods and properties.
 */
const DotdigitalMarketingConsentForm = (form, wire) => {

    return Object.assign({},
        hyva.formValidation(form),
        {
            init() {
                hyvaCheckout.navigation.addTask(() => this.validate())
                hyvaCheckout.navigation.addTask(() => this.submit())
            },

            async submit() {
                try {
                    await this.validate()
                } catch (e) {
                    return false
                }

                wire.save(await this.$dotdigitalFormDataCollection(this.$el))
                wire.refresh()
            }
        })
}

/**
 * Register the component on Alpine.js directly on the alpine:init event.
 *
 * @returns {DotdigitalMarketingConsentForm}
 */
export function  DotdigitalMarketingConsentFormComponent() {
    /**
     * Register the component on Alpine.js directly on the alpine:init event.
     *
     * @var {Component} this.$wire - The Livewire component instance.
     * @var {Element} this.$wire - The DOM element.
     */
    return  DotdigitalMarketingConsentForm(this.$el, this.$wire)
}

