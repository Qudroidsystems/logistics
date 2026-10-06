// Calculate and display age
function showage(dob, targetId = 'addAge') {
    if (!dob) return;
    const birthDate = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    // Update the age display (span)
    const ageDisplay = document.getElementById(targetId);
    if (ageDisplay) {
        if (ageDisplay.tagName === 'SPAN') {
            ageDisplay.textContent = `Age: ${age} years`;
        } else if (ageDisplay.tagName === 'INPUT') {
            ageDisplay.value = age;
        }
    } else {
        console.warn(`Age display element with ID '${targetId}' not found`);
    }
    // Update the age input field if different from display
    const ageInputId = targetId === 'addAge' ? 'age1' : 'editAge';
    const ageInput = document.getElementById(ageInputId);
    if (ageInput) {
        ageInput.value = age;
    }
}