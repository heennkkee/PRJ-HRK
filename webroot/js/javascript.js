function toggle(e) {
    var current = e.parentNode.style.maxHeight;

    if (current === '30px') {
        e.parentNode.style.maxHeight = '';
    } else {
        e.parentNode.style.maxHeight = '30px';
    }
}
