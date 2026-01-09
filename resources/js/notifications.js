/**
 * Sistema de Notificaciones en Tiempo Real
 * Requiere Laravel Echo y Pusher configurado
 */

// Configuración básica para notificaciones
// Nota: Requiere configurar Laravel Echo y Pusher en el frontend

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si Echo está disponible
    if (typeof Echo !== 'undefined') {
        initRealTimeNotifications();
    } else {
        console.warn('Laravel Echo no está configurado. Las notificaciones en tiempo real no funcionarán.');
    }
});

function initRealTimeNotifications() {
    const restaurantId = document.querySelector('meta[name="restaurant-id"]')?.content;
    
    if (!restaurantId) {
        console.warn('Restaurant ID no encontrado');
        return;
    }

    // Escuchar canal privado del restaurante
    Echo.private(`restaurant.${restaurantId}`)
        .listen('.order.created', (e) => {
            showNotification('Nuevo Pedido', e.message, 'info');
            // Recargar lista de pedidos si estamos en esa página
            if (window.location.pathname.includes('/orders')) {
                setTimeout(() => window.location.reload(), 2000);
            }
        })
        .listen('.order.status.changed', (e) => {
            showNotification('Estado de Pedido', e.message, 'warning');
            // Recargar si estamos viendo pedidos o cocina
            if (window.location.pathname.includes('/orders') || window.location.pathname.includes('/kitchen')) {
                setTimeout(() => window.location.reload(), 2000);
            }
        })
        .listen('.kitchen.order.ready', (e) => {
            showNotification('Pedido Listo', e.message, 'success');
            // Recargar dashboard o vista de pedidos
            if (window.location.pathname.includes('/dashboard') || window.location.pathname.includes('/orders')) {
                setTimeout(() => window.location.reload(), 2000);
            }
        });

    // Escuchar canal público de órdenes
    Echo.channel('orders')
        .listen('.order.created', (e) => {
            // Notificación global
        })
        .listen('.order.status.changed', (e) => {
            // Actualización global
        });
}

function showNotification(title, message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        <strong>${title}</strong><br>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

