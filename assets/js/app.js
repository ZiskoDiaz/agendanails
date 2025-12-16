// Funciones globales JavaScript para la aplicación

// Función para mostrar/ocultar modal
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Confirmar eliminación
function confirmarEliminacion(mensaje = '¿Estás segura de que deseas eliminar este elemento?') {
    return confirm(mensaje);
}

// Función para formatear moneda chilena
function formatCurrency(amount) {
    return '$' + new Intl.NumberFormat('es-CL').format(amount);
}

// Validar formularios
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('[required]');
    let valid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#dc2626';
            valid = false;
        } else {
            input.style.borderColor = '#e5e7eb';
        }
    });
    
    return valid;
}

// Auto-hide alerts después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Función para calcular total de servicios
function calcularTotal() {
    let total = 0;
    const servicios = document.querySelectorAll('input[name="servicios[]"]:checked');
    
    servicios.forEach(servicio => {
        total += parseFloat(servicio.dataset.precio || 0);
    });
    
    const totalElement = document.getElementById('total-cita');
    if (totalElement) {
        totalElement.textContent = formatCurrency(total);
    }
}

// Función para buscar en tablas
function buscarEnTabla(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const tabla = document.getElementById(tablaId);
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = tabla.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });
}

// Función para actualizar stock
function actualizarStock(insumoId, cantidad, tipo) {
    if (!confirm('¿Confirmar movimiento de inventario?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('insumo_id', insumoId);
    formData.append('cantidad', cantidad);
    formData.append('tipo', tipo);
    formData.append('motivo', 'Ajuste manual');
    
    fetch('ajax/actualizar_stock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar stock');
    });
}

// Calendario mejorado
class SimpleCalendar {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.currentDate = new Date();
        this.appointments = {};
        this.options = {
            allowNavigation: true,
            allowSelection: true,
            baseUrl: options.baseUrl || '',
            ...options
        };
        
        if (!this.container) {
            console.error('Contenedor del calendario no encontrado:', containerId);
            return;
        }
        
        this.render();
    }
    
    render() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        let html = `
            <div class="calendar-header">
                <button type="button" class="calendar-nav" onclick="window.calendar.previousMonth()">‹</button>
                <h3>${this.getMonthName(month)} ${year}</h3>
                <button type="button" class="calendar-nav" onclick="window.calendar.nextMonth()">›</button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-day-header">Dom</div>
                <div class="calendar-day-header">Lun</div>
                <div class="calendar-day-header">Mar</div>
                <div class="calendar-day-header">Mié</div>
                <div class="calendar-day-header">Jue</div>
                <div class="calendar-day-header">Vie</div>
                <div class="calendar-day-header">Sáb</div>
        `;
        
        const currentDate = new Date(startDate);
        for (let week = 0; week < 6; week++) {
            for (let day = 0; day < 7; day++) {
                const dateStr = currentDate.toISOString().split('T')[0];
                const isCurrentMonth = currentDate.getMonth() === month;
                const hasAppointments = this.appointments[dateStr] > 0;
                const isToday = dateStr === todayStr;
                const isPast = currentDate < today && dateStr !== todayStr;
                
                let classes = ['calendar-day'];
                if (!isCurrentMonth) classes.push('other-month');
                if (hasAppointments) classes.push('has-appointment');
                if (isToday) classes.push('today');
                if (isPast) classes.push('past-date');
                
                const appointmentCount = hasAppointments ? 
                    `<small class="appointment-count">${this.appointments[dateStr]} cita${this.appointments[dateStr] > 1 ? 's' : ''}</small>` : '';
                
                html += `
                    <div class="${classes.join(' ')}" 
                         onclick="window.calendar.selectDate('${dateStr}')" 
                         data-date="${dateStr}">
                        <span class="day-number">${currentDate.getDate()}</span>
                        ${appointmentCount}
                    </div>
                `;
                
                currentDate.setDate(currentDate.getDate() + 1);
            }
        }
        
        html += '</div>';
        this.container.innerHTML = html;
    }
    
    previousMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.render();
        this.updateUrl();
    }
    
    nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.render();
        this.updateUrl();
    }
    
    selectDate(dateStr) {
        if (!this.options.allowSelection) return;
        
        // Si estamos en la página de agenda, navegar a la vista de día
        if (window.location.pathname.includes('agenda')) {
            const baseUrl = window.location.pathname.includes('agenda/') ? '' : 'agenda/';
            window.location.href = `${baseUrl}index.php?view=dia&fecha=${dateStr}`;
        } else {
            // Desde el dashboard, ir a nueva cita con fecha preseleccionada
            window.location.href = `agenda/nueva_cita.php?fecha=${dateStr}`;
        }
    }
    
    updateUrl() {
        if (this.options.allowNavigation && window.history && window.history.replaceState) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('fecha', this.currentDate.toISOString().split('T')[0]);
            window.history.replaceState({}, '', currentUrl);
        }
    }
    
    setCurrentDate(dateStr) {
        if (dateStr) {
            this.currentDate = new Date(dateStr + 'T12:00:00');
            this.render();
        }
    }
    
    getMonthName(month) {
        const months = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return months[month];
    }
    
    setAppointments(appointments) {
        this.appointments = appointments || {};
        this.render();
    }
    
    // Método para actualizar las citas sin recargar todo el calendario
    updateAppointments(appointments) {
        this.appointments = appointments || {};
        
        // Actualizar solo los números de citas
        const days = this.container.querySelectorAll('.calendar-day[data-date]');
        days.forEach(day => {
            const dateStr = day.getAttribute('data-date');
            const hasAppointments = this.appointments[dateStr] > 0;
            
            // Actualizar clases
            if (hasAppointments) {
                day.classList.add('has-appointment');
                // Actualizar o agregar contador
                let counter = day.querySelector('.appointment-count');
                if (counter) {
                    counter.textContent = `${this.appointments[dateStr]} cita${this.appointments[dateStr] > 1 ? 's' : ''}`;
                } else {
                    const dayNumber = day.querySelector('.day-number');
                    dayNumber.insertAdjacentHTML('afterend', 
                        `<small class="appointment-count">${this.appointments[dateStr]} cita${this.appointments[dateStr] > 1 ? 's' : ''}</small>`
                    );
                }
            } else {
                day.classList.remove('has-appointment');
                const counter = day.querySelector('.appointment-count');
                if (counter) counter.remove();
            }
        });
    }
}

// Inicializar calendario si existe el contenedor
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('calendar')) {
        window.calendar = new SimpleCalendar('calendar');
    }
});