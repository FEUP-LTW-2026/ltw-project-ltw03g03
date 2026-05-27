document.addEventListener('DOMContentLoaded', () => {
    const slots = document.querySelectorAll('.pt-slot:not(.pt-slot--booked)');
    const submitBtn = document.querySelector('.booking-form__submit .btn-primary');
    
    slots.forEach(slot => {
        slot.addEventListener('click', () => {
            // Remove selection from all
            document.querySelectorAll('.pt-slot').forEach(el => el.classList.remove('is-selected'));
            // Add to clicked
            slot.classList.add('is-selected');
            // Enable button
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        });
    });
});
