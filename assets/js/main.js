const appointmentForm = document.getElementById('appointmentForm');

if (appointmentForm) {
    const vehicleList = document.getElementById('vehicleList');
    const addCarButton = document.getElementById('addCarButton');
    const vehicleTemplate = document.getElementById('vehicleTemplate');

    const renumberVehicles = () => {
        const cards = appointmentForm.querySelectorAll('.vehicle-card');
        cards.forEach((card, index) => {
            const number = index + 1;
            card.dataset.vehicleIndex = String(number);
            const title = card.querySelector('h4');
            if (title) {
                title.textContent = `Vehicle ${number}`;
            }
        });
    };

    const attachEngineValidation = (context) => {
        const engineInputs = context.querySelectorAll('input[name="car_engine_no[]"]');

        engineInputs.forEach((engineInput) => {
            engineInput.addEventListener('input', () => {
                if (/\D/.test(engineInput.value)) {
                    engineInput.setCustomValidity('Car engine number must contain numbers only.');
                } else {
                    engineInput.setCustomValidity('');
                }
            });
        });
    };

    if (addCarButton && vehicleTemplate && vehicleList) {
        addCarButton.addEventListener('click', () => {
            const vehicleCount = vehicleList.querySelectorAll('.vehicle-card').length;
            const nextIndex = vehicleCount + 1;
            let card = null;

            if ('content' in vehicleTemplate && vehicleTemplate.content) {
                const fragment = vehicleTemplate.content.cloneNode(true);
                card = fragment.firstElementChild;
            } else {
                const markup = vehicleTemplate.innerHTML.split('__INDEX__').join(String(nextIndex));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = markup.trim();
                card = wrapper.firstElementChild;
            }

            if (card) {
                card.dataset.vehicleIndex = String(nextIndex);
                const title = card.querySelector('h4');
                if (title) {
                    title.textContent = `Vehicle ${nextIndex}`;
                }

                vehicleList.appendChild(card);
                attachEngineValidation(card);
                renumberVehicles();
            }
        });

        vehicleList.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (!target.classList.contains('remove-car-btn')) {
                return;
            }

            const card = target.closest('.vehicle-card');
            if (!card) {
                return;
            }

            card.remove();
            renumberVehicles();
        });
    }

    attachEngineValidation(appointmentForm);

    appointmentForm.addEventListener('submit', (event) => {
        const phone = appointmentForm.querySelector('input[name="phone"]');
        const carRegs = appointmentForm.querySelectorAll('input[name="car_reg_no[]"]');
        const mechanics = appointmentForm.querySelectorAll('select[name="mechanic_id[]"]');
        const engineInputs = appointmentForm.querySelectorAll('input[name="car_engine_no[]"]');

        if (!phone || !phone.value.trim()) {
            event.preventDefault();
            alert('Please fill all required fields before submitting.');
            return;
        }

        if (phone.value.trim().length < 7) {
            event.preventDefault();
            alert('Please enter a valid phone number.');
            return;
        }

        for (let i = 0; i < carRegs.length; i += 1) {
            const carReg = carRegs[i];
            const mechanic = mechanics[i];
            const engineInput = engineInputs[i];

            if (!carReg || !mechanic || !engineInput) {
                event.preventDefault();
                alert('Vehicle details are incomplete.');
                return;
            }

            if (!carReg.value.trim() || !mechanic.value) {
                event.preventDefault();
                alert(`Please complete all required fields for Vehicle ${i + 1}.`);
                return;
            }

            if (/\D/.test(engineInput.value.trim())) {
                event.preventDefault();
                alert(`Car engine number must contain numbers only for Vehicle ${i + 1}.`);
                return;
            }
        }
    });
}
