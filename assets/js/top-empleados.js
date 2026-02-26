console.log('🔥 TOP EMPLEADOS v7 - CARGANDO...');

$(document).ready(function() {

    let topEmpleadosChart = null;
    let actividadSeleccionada = 'all';

    console.log('🏆 Top Empleados v7 - Inicializando...');

    // ============================================
    // CARGAR DROPDOWN DE ACTIVIDADES DINÁMICAMENTE
    // ============================================
    window.cargarDropdownActividades = function() {
        const departamento = window.departamentoActual || 'all';

        $.ajax({
            url: 'api/actividades_departamento.php',
            method: 'GET',
            data: { departamento: departamento },
            dataType: 'json',
            success: function(data) {
                renderizarDropdownActividades(data);
            },
            error: function() {
                console.error('Error al cargar actividades');
            }
        });
    };

    function renderizarDropdownActividades(data) {
        let html = '';

        html += '<h6 class="dropdown-header">General</h6>';
        html += '<a class="dropdown-item filtro-actividad" href="#" data-actividad="all">Todos</a>';

        if (data.mostrar_todos && data.departamentos) {
            data.departamentos.forEach(function(dept) {
                if (dept.actividades && dept.actividades.length > 0) {
                    html += '<div class="dropdown-divider"></div>';
                    html += '<h6 class="dropdown-header">' + dept.nombre + '</h6>';
                    dept.actividades.forEach(function(act) {
                        html += '<a class="dropdown-item filtro-actividad" href="#" data-actividad="' + act.id + '">' + act.nombre + '</a>';
                    });
                }
            });
        } else if (data.actividades && data.actividades.length > 0) {
            html += '<div class="dropdown-divider"></div>';
            html += '<h6 class="dropdown-header">' + (data.departamento_actual?.nombre || 'Actividades') + '</h6>';
            data.actividades.forEach(function(act) {
                html += '<a class="dropdown-item filtro-actividad" href="#" data-actividad="' + act.id + '">' + act.nombre + '</a>';
            });
        }

        $('#dropdownActividadesMenu').html(html);

        $('.filtro-actividad').off('click').on('click', function(e) {
            e.preventDefault();
            actividadSeleccionada = $(this).data('actividad');
            $('#filtroActividadTexto').text($(this).text());
            actualizarTopEmpleados();
        });
    }

    setTimeout(function() {
        cargarDropdownActividades();
    }, 500);

    window.actualizarTopEmpleados = function() {
        console.log('📊 Actualizando Top Empleados v7...');

        let params = {
            periodo: window.periodoActual || 'semana',
            departamento: window.departamentoActual || 'all',
            actividad: actividadSeleccionada || 'all'
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
                $('#marketingOverview').css('opacity', '1');
                $('#topEmpleadoNombre').text('Error al cargar');
                $('#topEmpleadoTickets').text('0 Completados');
                $('#topEmpleadoPorcentaje').text('(0%)');
            }
        });
    };

    function renderizarGrafico(data) {
        console.log('🎨 Renderizando gráfico v7...');

        const canvas = document.getElementById("marketingOverview");
        if (!canvas) {
            console.error('❌ Canvas #marketingOverview NO encontrado');
            return;
        }

        const ctx = canvas.getContext('2d');

        if (topEmpleadosChart) {
            topEmpleadosChart.destroy();
            topEmpleadosChart = null;
        }

        const empleados = data.empleados || [];

        if (empleados.length === 0) {
            $('#marketingOverview').css('opacity', '1');
            $('#topEmpleadoNombre').text('Sin datos');
            $('#topEmpleadoTickets').text('0 Completados');
            $('#topEmpleadoPorcentaje').text('(0%)');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        // =====================================================
        // GANADOR ARRIBA:
        // Los datos vienen ordenados ASC desde PHP (menor a mayor).
        // Chart.js horizontalBar dibuja el PRIMER elemento abajo y el ÚLTIMO arriba.
        // Así el ganador (último del array ASC) aparece en la barra superior.
        // =====================================================

        const empleadosParaGrafico = empleados;

        const labels = empleadosParaGrafico.map(e => e.nombre);
        const valores = empleadosParaGrafico.map(e => e.tickets);

        console.log('Empleados ASC (ganador al final):', empleados.map(e => e.nombre + ':' + e.tickets));

        const maxTickets = Math.max(...valores, 1);
        const stepSize = Math.ceil(maxTickets / 5);
        const maxRange = maxTickets + (stepSize * 2);

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
                layout: {
                    padding: {
                        right: 80
                    }
                },
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
                        },
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: 'rgba(31, 59, 179, 0.9)',
                    callbacks: {
                        label: function(tooltipItem) {
                            const idx = tooltipItem.index;
                            const emp = empleadosParaGrafico[idx];
                            return emp.tickets + ' tickets (' + emp.porcentaje + '%)';
                        }
                    }
                },
                animation: {
                    duration: 800,
                    onComplete: function() {
                        dibujarEtiquetas(this, empleadosParaGrafico);
                    }
                }
            }
        });

        // ACTUALIZAR INFO DEL GANADOR (primer elemento de datos originales)
        if (data.ganador) {
            $('#topEmpleadoNombre').text(data.ganador.nombre);
            $('#topEmpleadoTickets').text(data.ganador.tickets + ' Completados');
            $('#topEmpleadoPorcentaje').text('(' + data.ganador.porcentaje + '%)');
        }

        $('#marketingOverview').css('opacity', '1');
        console.log('✅ Gráfico v7 renderizado');
    }

    function dibujarEtiquetas(chart, empleadosParaGrafico) {
        const ctx = chart.chart.ctx;

        chart.data.datasets.forEach(function(dataset, i) {
            const meta = chart.chart.getDatasetMeta(i);

            meta.data.forEach(function(bar, index) {
                const emp = empleadosParaGrafico[index];
                const value = emp.tickets;
                const pct = emp.porcentaje;

                const barX = bar._model.x;
                const barY = bar._model.y;
                const barBase = bar._model.base;
                const barWidth = barX - barBase;

                // Etiqueta al final: "X Tickets"
                ctx.fillStyle = '#2c3e50';
                ctx.font = 'bold 11px Arial';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(value + ' Tickets', barX + 8, barY);

                // Porcentaje dentro de la barra
                if (barWidth > 50) {
                    ctx.fillStyle = '#ffffff';
                    ctx.font = 'bold 10px Arial';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    const centerX = barBase + (barWidth / 2);
                    ctx.fillText(pct + '%', centerX, barY);
                }
            });
        });
    }

    $(document).on('dashboardFiltersChanged', function() {
        actividadSeleccionada = 'all';
        $('#filtroActividadTexto').text('Todos');
        cargarDropdownActividades();
        actualizarTopEmpleados();
    });

    setTimeout(function() {
        actualizarTopEmpleados();
    }, 1000);

});
