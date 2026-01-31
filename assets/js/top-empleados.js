console.log('🔥 TOP EMPLEADOS - CARGANDO...');

$(document).ready(function() {
    
    let topEmpleadosChart = null;
    
    console.log('🏆 Top Empleados - Inicializando...');
    
    window.actualizarTopEmpleados = function() {
        console.log('📊 Actualizando Top Empleados...');
        
        let params = {
            periodo: window.periodoActual || 'semana',
            departamento: window.departamentoActual || 'all'
        };
        
        console.log('Parámetros:', params);
        
        $('#marketingOverview').css('opacity', '0.3');
        
        $.ajax({
            url: 'api/top_empleados.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Datos recibidos:', response);
                renderizarGrafico(response);
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                $('#marketingOverview').css('opacity', '1');
                // Mostrar mensaje sin datos en lugar de alert
                $('#topEmpleadoNombre').text('Error al cargar');
                $('#topEmpleadoTickets').text('0 Completados');
                $('#topEmpleadoPorcentaje').text('(0%)');
            }
        });
    };
    
    function renderizarGrafico(data) {
        console.log('🎨 Renderizando gráfico...');
        
        const canvas = document.getElementById("marketingOverview");
        if (!canvas) {
            console.error('❌ Canvas #marketingOverview NO encontrado');
            return;
        }
        
        console.log('✅ Canvas encontrado');
        
        const ctx = canvas.getContext('2d');
        
        if (topEmpleadosChart) {
            console.log('🗑️ Destruyendo gráfico anterior');
            topEmpleadosChart.destroy();
        }
        
        const empleados = data.empleados || [];
        
        console.log('Empleados recibidos:', empleados.length);
        
        // ✅ SI NO HAY DATOS, MOSTRAR MENSAJE
        if (empleados.length === 0) {
            console.warn('⚠️ No hay usuarios en el sistema');
            $('#marketingOverview').css('opacity', '1');
            $('#topEmpleadoNombre').text('No hay usuarios');
            $('#topEmpleadoTickets').text('0 Completados');
            $('#topEmpleadoPorcentaje').text('(0%)');
            return;
        }
        
        // ✅ INVERTIR ORDEN (primero arriba)
        const empleadosOrdenados = [...empleados].reverse();
        
        const labels = empleadosOrdenados.map(e => e.nombre);
        const valores = empleadosOrdenados.map(e => e.tickets);
        
        console.log('Labels:', labels);
        console.log('Valores:', valores);
        
        // ✅ CALCULAR RANGO DINÁMICO DEL EJE X
        const maxTickets = Math.max(...valores, 1); // Mínimo 1 para evitar 0
        const stepSize = Math.ceil(maxTickets / 5); // Dividir en 5 pasos
        const maxRange = maxTickets + stepSize; // Agregar margen
        
        console.log('Rango X:', {max: maxTickets, step: stepSize, range: maxRange});
        
        // ✅ GRÁFICO HORIZONTAL
        topEmpleadosChart = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tickets Resueltos',
                    data: valores,
                    backgroundColor: 'rgba(31, 59, 179, 0.8)',
                    borderColor: 'rgba(31, 59, 179, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            stepSize: stepSize > 0 ? stepSize : 1,
                            max: maxRange,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        },
                        gridLines: {
                            display: true,
                            color: '#F0F0F0'
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            fontSize: 11,
                            fontColor: '#2c3e50'
                        },
                        gridLines: {
                            display: false
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: 'rgba(31, 59, 179, 0.9)',
                    titleFontSize: 13,
                    bodyFontSize: 12,
                    xPadding: 12,
                    yPadding: 12,
                    cornerRadius: 6,
                    callbacks: {
                        label: function(tooltipItem) {
                            const value = tooltipItem.xLabel;
                            return value + ' tickets resueltos';
                        }
                    }
                },
                animation: {
                    duration: 1000
                }
            }
        });
        
        // ✅ ACTUALIZAR INFO DEL GANADOR
        if (data.ganador) {
            $('#topEmpleadoNombre').text(data.ganador.nombre);
            $('#topEmpleadoTickets').text(data.ganador.tickets + ' Completados');
            $('#topEmpleadoPorcentaje').text('(' + data.ganador.porcentaje + '%)');
        }
        
        $('#marketingOverview').css('opacity', '1');
        
        console.log('✅ Gráfico renderizado exitosamente');
    }
    
    $(document).on('dashboardFiltersChanged', function() {
        console.log('🔄 Evento dashboardFiltersChanged detectado');
        actualizarTopEmpleados();
    });
    
    console.log('⏳ Esperando 1 segundo para cargar datos iniciales...');
    
    setTimeout(function() {
        console.log('🚀 Cargando datos iniciales de Top Empleados...');
        actualizarTopEmpleados();
    }, 1000);
    
});