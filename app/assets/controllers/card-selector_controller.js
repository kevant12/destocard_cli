import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['numberInput', 'cardSelect', 'selectedCardId'];

    connect() {
        // console.log('Card selector connected');
    }

    async onExtensionChange(event) {
        const extensionId = event.currentTarget.value;
        this.cardSelectTarget.innerHTML = '<option>Chargement...</option>';

        if (!extensionId) {
            this.cardSelectTarget.innerHTML = '<option>--- Sélectionner une carte ---</option>';
            return;
        }

        const response = await fetch(`/api/cards?extensionId=${extensionId}`);
        const cards = await response.json();

        let options = '<option>--- Sélectionner une carte ---</option>';
        cards.forEach(card => {
            options += `<option value="${card.id}">${card.name} (${card.number})</option>`;
        });

        this.cardSelectTarget.innerHTML = options;
    }

    async onNumberInput(event) {
        const cardNumber = event.currentTarget.value;
        const extensionId = this.element.querySelector('[data-action="change->card-selector#onExtensionChange"]').value;

        if (!extensionId || !cardNumber) {
            return;
        }

        const response = await fetch(`/api/card?extensionId=${extensionId}&cardNumber=${cardNumber}`);
        if (response.status === 404) {
            // Optionnel: gérer le cas où la carte n'est pas trouvée
            return;
        }
        const card = await response.json();

        if (card) {
            // Mettre à jour la liste déroulante et la sélectionner
            let optionExists = this.cardSelectTarget.querySelector(`option[value="${card.id}"]`);
            if (!optionExists) {
                const newOption = new Option(`${card.name} (${card.number})`, card.id, true, true);
                this.cardSelectTarget.add(newOption);
            }
            this.cardSelectTarget.value = card.id;

            // Déclencher manuellement l'événement de changement pour mettre à jour d'autres parties du formulaire si nécessaire
            const changeEvent = new Event('change');
            this.cardSelectTarget.dispatchEvent(changeEvent);
        }
    }

    onCardChange(event) {
        // Mettre à jour un champ caché avec l'ID de la carte si nécessaire
        // if (this.hasSelectedCardIdTarget) {
        //     this.selectedCardIdTarget.value = event.currentTarget.value;
        // }
    }
}
