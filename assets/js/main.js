const appointmentForm = document.getElementById('appointmentForm');

if (appointmentForm) {
    const engineNo = appointmentForm.querySelector('input[name="car_engine_no"]');

    if (engineNo) {
        engineNo.addEventListener('input', () => {
            if (/\D/.test(engineNo.value)) {
                engineNo.setCustomValidity('Car engine number must contain numbers only.');
            } else {
                engineNo.setCustomValidity('');
            }
        });
    }

    appointmentForm.addEventListener('submit', (event) => {
        const phone = appointmentForm.querySelector('input[name="phone"]');
        const carReg = appointmentForm.querySelector('input[name="car_reg_no"]');
        const mechanic = appointmentForm.querySelector('select[name="mechanic_id"]');

        if (!phone.value.trim() || !carReg.value.trim() || !mechanic.value) {
            event.preventDefault();
            alert('Please fill all required fields before submitting.');
            return;
        }

        if (phone.value.trim().length < 7) {
            event.preventDefault();
            alert('Please enter a valid phone number.');
            return;
        }

        if (engineNo && /\D/.test(engineNo.value.trim())) {
            event.preventDefault();
            alert('Car engine number must contain numbers only.');
        }
    });
}
