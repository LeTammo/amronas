/* Scrolls to an entry of the list and highlights it, opening its folded group first. */
export function revealEntry(element) {
    const group = element.closest('details');
    if (group && !group.open) group.open = true;
    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    element.classList.remove('highlight');
    void element.offsetWidth; // restart the animation
    element.classList.add('highlight');
}
