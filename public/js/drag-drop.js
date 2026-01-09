/**
 * Funcionalidad de Drag & Drop para layout de mesas
 * Usa Interact.js para arrastrar y soltar elementos
 */

// Inicializar drag and drop cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en la página de layout
    if (document.getElementById('layoutCanvas')) {
        initTableLayoutDragDrop();
    }
});

function initTableLayoutDragDrop() {
    const canvas = document.getElementById('layoutCanvas');
    if (!canvas) return;

    // Configurar interact.js para cada mesa
    interact('.table-item').draggable({
        listeners: {
            start(event) {
                event.target.classList.add('dragging');
            },
            move(event) {
                const target = event.target;
                const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                // Aplicar transformación
                target.style.transform = `translate(${x}px, ${y}px)`;
                
                // Guardar posición relativa
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            },
            end(event) {
                event.target.classList.remove('dragging');
            }
        },
        modifiers: [
            interact.modifiers.restrictRect({
                restriction: 'parent',
                endOnly: true
            })
        ],
        inertia: false
    });
}

// Exportar función para uso global
window.initTableLayoutDragDrop = initTableLayoutDragDrop;

