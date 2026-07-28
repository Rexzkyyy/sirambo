import { initAdjInput } from './adj-input';

document.addEventListener('DOMContentLoaded', () => {
    initAdjInput();

    document.querySelectorAll('.total-adj').forEach(i => i.readOnly = true);

    console.log('Rekon P1 ready');
});
