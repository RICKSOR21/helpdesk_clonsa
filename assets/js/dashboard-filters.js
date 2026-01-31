/**
 * =============================================
 * DASHBOARD FILTERS - Sistema de filtros dinámicos v4.0
 * =============================================
 */

$(document).ready(function() {
    
    // ✅ EXPONER VARIABLES GLOBALMENTE
    window.periodoActual = 'semana';
    window.departamentoActual = 'all';
    window.fechaDesdePickr = null;
    window.fechaHastaPickr = null;
    
    let performanceChart = null;
    let actividadesSeleccionadas = {};
    
    console.log('🚀 Inicializando Dashboard Filters v4.0...');
    
    // ============================================
    // INICIALIZAR FLATPICKR
    // ============================================
    
    window.fechaDesdePickr = flatpickr("#fechaDesde", {
        dateFormat: "d/m/Y",
        locale: "es",
        allowInput: false,
        clickOpens: false,
        defaultDate: new Date(Date.now() - 7*24*60*60*1000)
    });
    
    window.fechaHastaPickr = flatpickr("#fechaHasta", {
        dateFormat: "d/m/Y",
        locale: "es",
        allowInput: false,
        clickOpens: false,
        defaultDate: new Date()
    });
    
    $('#fechaDesde, #fechaHasta').css({
        'background-color': '#f5f5f5',
        'cursor': 'not-allowed',
        'opacity': '0.7'
    });
    
    console.log('✅ Flatpickr inicializado');
    
    // ============================================
    // FUNCIÓN PRINCIPAL: ACTUALIZAR DASHBOARD
    // ============================================
    
    function actualizarDashboard() {
        console.log('🔄 Actualizando dashboard...', {
            periodo: window.periodoActual,
            departamento: window.departamentoActual
        });
        
        mostrarLoading();
        mostrarLoadingCirculos();
        mostrarLoadingGrafico();
        
        let params = {
            periodo: window.periodoActual,
            departamento: window.departamentoActual
        };
        
        if (window.periodoActual === 'personalizado') {
            params.fecha_desde = window.fechaDesdePickr.input.value;
            params.fecha_hasta = window.fechaHastaPickr.input.value;
        }
        
        $.ajax({
            url: 'api/dashboard_data.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Datos recibidos:', response);
                actualizarCards(response.metricas, response.comparativas);
                ocultarLoading();
            },
            error: function(xhr, status, error) {
                console.error('❌ Error al cargar datos:', error);
                mostrarError('Error al cargar los datos del dashboard.');
                ocultarLoading();
            }
        });
        
        actualizarGraficoMantenimientos();
        actualizarStatusSummary();
        
        // ✅ ACTUALIZAR TOP EMPLEADOS
        if (typeof window.actualizarTopEmpleados === 'function') {
            window.actualizarTopEmpleados();
        }
        
        // ✅ EMITIR EVENTO PARA OTROS COMPONENTES
        $(document).trigger('dashboardFiltersChanged');
    }
    
    // ============================================
    // FUNCIONES DE LOADING
    // ============================================
    
    function mostrarLoadingCirculos() {
        $('#actividadesCirculos').html(`
            <div class="col-12 text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `);
    }
    
    function mostrarLoadingGrafico() {
        const loading = document.getElementById('chart-loading');
        if (loading) loading.style.display = 'flex';
        const canvas = document.getElementById('performaneLine');
        if (canvas) canvas.style.opacity = '0';
    }
    
    function ocultarLoadingGrafico() {
        const loading = document.getElementById('chart-loading');
        if (loading) loading.style.display = 'none';
        const canvas = document.getElementById('performaneLine');
        if (canvas) canvas.style.opacity = '1';
    }
    
    // ============================================
    // FUNCIÓN: ACTUALIZAR STATUS SUMMARY
    // ============================================
    
    function actualizarStatusSummary() {
        console.log('📊 Actualizando Status Summary...');
        
        let params = {
            periodo: window.periodoActual,
            departamento: window.departamentoActual
        };
        
        if (window.periodoActual === 'personalizado') {
            params.fecha_desde = window.fechaDesdePickr.input.value;
            params.fecha_hasta = window.fechaHastaPickr.input.value;
        }
        
        $.ajax({
            url: 'api/status_summary.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Status Summary recibido:', response);
                renderizarStatusSummary(response);
            },
            error: function(xhr, status, error) {
                console.error('❌ Error al cargar Status Summary:', error);
                renderizarStatusSummary({total_tickets: 0, top_actividades: []});
            }
        });
    }
    
    // ============================================
    // FUNCIÓN: RENDERIZAR STATUS SUMMARY
    // ============================================
    
    function renderizarStatusSummary(data) {
        console.log('🎨 Renderizando Status Summary:', data);
        
        $('#totalTicketsValue').text(data.total_tickets);
        $('#actividadesCirculos').empty();
        
        const actividades = data.top_actividades || [];
        
        if (actividades.length === 0) {
            $('#actividadesCirculos').html(`
                <div class="col-12 text-center py-3">
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">No hay actividades registradas</p>
                </div>
            `);
            return;
        }
        
        actividades.forEach(function(actividad, index) {
            const containerId = 'actividad-' + actividad.id + '-' + index;
            
            const html = `
                <div class="col-sm-6 col-lg-6 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="circle-progress-container">
                            <div id="${containerId}" class="progressbar-js-circle"></div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="activity-name mb-0">${truncarTexto(actividad.nombre, 30)}</p>
                            <small class="text-muted">${actividad.cantidad || 0} tickets</small>
                        </div>
                    </div>
                </div>
            `;
            
            $('#actividadesCirculos').append(html);
            
            const progressbar = new ProgressBar.Circle('#' + containerId, {
                color: actividad.color || '#CCCCCC',
                strokeWidth: 6,
                trailWidth: 6,
                trailColor: '#f0f0f0',
                easing: 'easeInOut',
                duration: 1400,
                text: {
                    autoStyleContainer: false
                },
                from: { color: actividad.color || '#CCCCCC', width: 6 },
                to: { color: actividad.color || '#CCCCCC', width: 6 },
                step: function(state, circle) {
                    circle.path.setAttribute('stroke', state.color);
                    circle.path.setAttribute('stroke-width', state.width);
                    
                    var value = Math.round(circle.value() * 100);
                    circle.setText(value + '%');
                }
            });
            
            progressbar.animate((actividad.porcentaje || 0) / 100);
        });
        
        console.log('✅ Status Summary renderizado con', actividades.length, 'actividades');
    }
    
    // ============================================
    // FUNCIÓN: TRUNCAR TEXTO
    // ============================================
    
    function truncarTexto(texto, maxLength) {
        if (!texto) return '';
        if (texto.length <= maxLength) return texto;
        
        let truncado = texto.substring(0, maxLength);
        const ultimoEspacio = truncado.lastIndexOf(' ');
        
        if (ultimoEspacio > 0) {
            truncado = truncado.substring(0, ultimoEspacio);
        }
        
        return truncado + '...';
    }
    
    // ============================================
    // FUNCIÓN: ACTUALIZAR GRÁFICO DE MANTENIMIENTOS
    // ============================================
    
    function actualizarGraficoMantenimientos() {
        console.log('📈 Actualizando gráfico de mantenimientos...');
        
        let params = {
            periodo: window.periodoActual,
            departamento: window.departamentoActual
        };
        
        if (window.periodoActual === 'personalizado') {
            params.fecha_desde = window.fechaDesdePickr.input.value;
            params.fecha_hasta = window.fechaHastaPickr.input.value;
        }
        
        $.ajax({
            url: 'api/dashboard_charts.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                console.log('✅ Datos gráfico recibidos:', response);
                renderizarGrafico(response);
            },
            error: function(xhr, status, error) {
                console.error('❌ Error al cargar gráfico:', error);
                ocultarLoadingGrafico();
            }
        });
    }
    
    // ============================================
    // FUNCIÓN: RENDERIZAR GRÁFICO
    // ============================================
    
    function renderizarGrafico(data) {
        const canvas = document.getElementById("performaneLine");
        if (!canvas) {
            console.warn('⚠️ Canvas performaneLine no encontrado');
            return;
        }
        
        const ctx = canvas.getContext('2d');
        
        if (performanceChart) {
            performanceChart.destroy();
        }
        
        const chartDatasets = [];
        const coloresPredefinidos = [
            '#1F3BB3', '#4CAF50', '#FF9800', '#E91E63', 
            '#9C27B0', '#00BCD4', '#FFC107', '#795548'
        ];
        
        data.datasets.forEach(function(dataset, index) {
            const color = dataset.color || coloresPredefinidos[index % coloresPredefinidos.length];
            
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            const rgb = hexToRgb(color);
            gradient.addColorStop(0, `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.18)`);
            gradient.addColorStop(1, `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.02)`);
            
            const estaVisible = actividadesSeleccionadas[dataset.id] !== false;
            
            chartDatasets.push({
                id: dataset.id,
                label: dataset.nombre,
                data: dataset.data,
                backgroundColor: gradient,
                borderColor: color,
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                hidden: !estaVisible
            });
        });
        
        performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: chartDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        gridLines: {
                            display: true,
                            drawBorder: false,
                            color: "#F0F0F0",
                            zeroLineColor: '#F0F0F0',
                        },
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1,
                            fontSize: 10,
                            fontColor: "#6B778C"
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            fontSize: 9,
                            fontColor: "#6B778C",
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }]
                },
                legend: false,
                tooltips: {
                    backgroundColor: 'rgba(31, 59, 179, 0.9)',
                    titleFontSize: 13,
                    bodyFontSize: 12,
                    xPadding: 12,
                    yPadding: 12,
                    cornerRadius: 6,
                    displayColors: true,
                    callbacks: {
                        label: function(tooltipItem, chartData) {
                            var dataset = chartData.datasets[tooltipItem.datasetIndex];
                            var value = dataset.data[tooltipItem.index];
                            return dataset.label + ': ' + value + ' tickets';
                        }
                    }
                }
            }
        });
        
        if (data.tipo_grafico === 'fijo') {
            renderizarLeyendaFija(data.actividades);
        } else {
            renderizarDropdownActividades(data.actividades);
        }
        
        ocultarLoadingGrafico();
        console.log('✅ Gráfico renderizado:', data.tipo_grafico, 'con', chartDatasets.length, 'datasets');
    }
    
    // ============================================
    // FUNCIÓN: RENDERIZAR LEYENDA FIJA
    // ============================================
    
    function renderizarLeyendaFija(actividades) {
        let html = '<div class="chartjs-legend"><ul class="legend-list">';
        
        actividades.forEach(function(actividad, index) {
            const meta = performanceChart.getDatasetMeta(index);
            const hidden = meta.hidden ? 'legend-item-hidden' : '';
            
            html += `
                <li class="legend-item ${hidden}" data-index="${index}">
                    <span class="legend-color" style="background-color: ${actividad.color}"></span>
                    <span class="legend-text">${actividad.nombre}</span>
                </li>
            `;
        });
        
        html += '</ul></div>';
        
        document.getElementById('performance-line-legend').innerHTML = html;
        
        $('#performance-line-legend .legend-item').on('click', function() {
            var index = $(this).data('index');
            var meta = performanceChart.getDatasetMeta(index);
            
            meta.hidden = meta.hidden === null ? !performanceChart.data.datasets[index].hidden : null;
            $(this).toggleClass('legend-item-hidden');
            performanceChart.update();
        });
    }
    
    // ============================================
    // FUNCIÓN: RENDERIZAR DROPDOWN DINÁMICO
    // ============================================
    
    function renderizarDropdownActividades(actividades) {
        const primeraActiva = actividades[0] || null;
        
        let html = `
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" 
                            type="button" 
                            id="dropdownActividades" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <i class="mdi mdi-filter-variant"></i>
                        <span>Filtrar Actividades</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownActividades" style="min-width: 250px;">
        `;
        
        actividades.forEach(function(actividad, index) {
            const meta = performanceChart.getDatasetMeta(index);
            const checked = !meta.hidden ? 'visible' : '';
            
            html += `
                <li>
                    <a class="dropdown-item d-flex align-items-center justify-content-between actividad-toggle ${checked}" 
                       href="#" 
                       data-index="${index}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="legend-color" style="background-color: ${actividad.color}; width: 12px; height: 12px; border-radius: 50%;"></span>
                            <span>${actividad.nombre}</span>
                        </div>
                        <i class="mdi mdi-check text-success actividad-check ${checked}" style="font-size: 1.2rem;"></i>
                    </a>
                </li>
            `;
        });
        
        html += `
                    </ul>
                </div>
                ${primeraActiva ? `
                <div class="selected-activity-badge">
                    <span class="badge" style="background-color: ${primeraActiva.color}; color: white; font-size: 0.75rem; padding: 4px 10px;">
                        <i class="mdi mdi-chart-line"></i> ${primeraActiva.nombre}
                    </span>
                </div>
                ` : ''}
            </div>
        `;
        
        document.getElementById('performance-line-legend').innerHTML = html;
        
        $('.actividad-toggle').on('click', function(e) {
            e.preventDefault();
            var index = $(this).data('index');
            var meta = performanceChart.getDatasetMeta(index);
            
            meta.hidden = meta.hidden === null ? !performanceChart.data.datasets[index].hidden : null;
            
            $(this).toggleClass('visible');
            $(this).find('.actividad-check').toggleClass('visible');
            
            actividadesSeleccionadas[performanceChart.data.datasets[index].id] = !meta.hidden;
            
            performanceChart.update();
        });
    }
    
    // ============================================
    // FUNCIÓN: HEX TO RGB
    // ============================================
    
    function hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : {r: 102, g: 126, b: 234};
    }
    
    // ============================================
    // FUNCIÓN: ACTUALIZAR CARDS
    // ============================================
    
    function actualizarCards(metricas, comparativas) {
        console.log('📊 Actualizando cards con métricas:', metricas);
        
        $('#ticketsAbiertos').text(metricas.tickets_abiertos);
        actualizarComparativa('#ticketsAbiertosComp', comparativas.tickets_abiertos);
        
        $('#ticketsProceso').text(metricas.tickets_proceso);
        actualizarComparativa('#ticketsProcesoComp', comparativas.tickets_proceso);
        
        $('#ticketsResueltos').text(metricas.tickets_resueltos);
        actualizarComparativa('#ticketsResueltosComp', comparativas.tickets_resueltos);
        
        $('#tiempoPromedio').text(metricas.tiempo_promedio);
        actualizarComparativa('#tiempoPromedioComp', comparativas.tiempo_promedio);
        
        $('#canalFrecuente').text(metricas.canal_frecuente);
        $('#canalFrecuenteTotal').text(`${metricas.canal_frecuente_total} Registros`);
        actualizarIndicadorEstatico('.statistics-details > div:nth-child(5) p.d-flex');
        
        $('#fallaFrecuente').text(metricas.falla_frecuente);
        $('#fallaFrecuenteTotal').text(`${metricas.falla_frecuente_total} Registros`);
        actualizarIndicadorEstatico('.statistics-details > div:nth-child(6) p.d-flex');
    }
    
    // ============================================
    // FUNCIÓN: ACTUALIZAR INDICADOR ESTÁTICO
    // ============================================
    
    function actualizarIndicadorEstatico(selector) {
        const elemento = $(selector);
        elemento.removeClass('text-success text-danger text-info');
        elemento.addClass('text-success');
        elemento.find('i').removeClass('mdi-menu-down mdi-menu-up mdi-minus');
        elemento.find('i').addClass('mdi-menu-up');
    }
    
    // ============================================
    // FUNCIÓN: ACTUALIZAR COMPARATIVA
    // ============================================
    
    function actualizarComparativa(selector, porcentaje) {
        const elemento = $(selector);
        
        if (porcentaje === 0 || porcentaje === '0' || porcentaje === '+0' || porcentaje === '-0') {
            elemento.removeClass('text-success text-danger text-info');
            elemento.addClass('text-success');
            elemento.html(`<i class="mdi mdi-minus"></i><span>±0%</span>`);
            return;
        }
        
        const esPositivo = parseFloat(porcentaje) > 0;
        const colorClase = esPositivo ? 'text-success' : 'text-danger';
        const icono = esPositivo ? 'mdi-menu-up' : 'mdi-menu-down';
        
        elemento.removeClass('text-success text-danger text-info');
        elemento.addClass(colorClase);
        
        const signo = esPositivo ? '+' : '';
        elemento.html(`<i class="mdi ${icono}"></i><span>${signo}${porcentaje}%</span>`);
    }
    
    // ============================================
    // EVENTOS
    // ============================================
    
    $('.nav-tabs .nav-link').on('click', function(e) {
        e.preventDefault();
        $('.nav-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        
        const tabId = $(this).attr('href');
        
        if (tabId === '#overview') window.periodoActual = 'semana';
        else if (tabId === '#audiences') window.periodoActual = 'mes';
        else if (tabId === '#demographics') window.periodoActual = 'año';
        else if (tabId === '#more') window.periodoActual = 'personalizado';
        
        if (window.periodoActual === 'personalizado') {
            window.fechaDesdePickr.set('clickOpens', true);
            window.fechaHastaPickr.set('clickOpens', true);
            $('#fechaDesde, #fechaHasta').css({
                'background-color': 'white',
                'cursor': 'pointer',
                'opacity': '1'
            });
            window.fechaDesdePickr.setDate(new Date(Date.now() - 7*24*60*60*1000));
            window.fechaHastaPickr.setDate(new Date());
        } else {
            window.fechaDesdePickr.set('clickOpens', false);
            window.fechaHastaPickr.set('clickOpens', false);
            $('#fechaDesde, #fechaHasta').css({
                'background-color': '#f5f5f5',
                'cursor': 'not-allowed',
                'opacity': '0.7'
            });
            const fechas = calcularFechas(window.periodoActual);
            window.fechaDesdePickr.setDate(fechas.desde);
            window.fechaHastaPickr.setDate(fechas.hasta);
            actualizarDashboard();
        }
    });
    
    window.fechaDesdePickr.config.onChange.push(function(selectedDates, dateStr) {
        if (window.periodoActual === 'personalizado') actualizarDashboard();
    });
    
    window.fechaHastaPickr.config.onChange.push(function(selectedDates, dateStr) {
        if (window.periodoActual === 'personalizado') actualizarDashboard();
    });
    
    $('.dropdown-menu a[data-departamento]').on('click', function(e) {
        e.preventDefault();
        if (!window.PUEDE_VER_TODOS) return false;
        
        const texto = $(this).find('p').text().trim();
        const deptId = $(this).data('departamento');
        
        $('#messageDropdown').text(texto);
        window.departamentoActual = deptId.toString();
        actividadesSeleccionadas = {};
        
        actualizarDashboard();
    });
    
    if (!window.PUEDE_VER_TODOS) {
        window.departamentoActual = window.USER_DEPARTAMENTO.toString();
    }
    
    function calcularFechas(periodo) {
        const hoy = new Date();
        let desde;
        
        switch(periodo) {
            case 'mes':
                desde = new Date(hoy);
                desde.setDate(hoy.getDate() - 30);
                break;
            case 'año':
                desde = new Date(hoy);
                desde.setFullYear(hoy.getFullYear() - 1);
                break;
            default:
                desde = new Date(hoy);
                desde.setDate(hoy.getDate() - 7);
        }
        
        return { desde, hasta: new Date() };
    }
    
    function mostrarLoading() {
        $('.statistics-details').css('opacity', '0.5');
    }
    
    function ocultarLoading() {
        $('.statistics-details').css('opacity', '1');
    }
    
    function mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#d33'
            });
        } else {
            console.error('Error:', mensaje);
        }
    }
    
    console.log('📊 Cargando datos iniciales...');
    actualizarDashboard();
    console.log('✅ Dashboard Filters v4.0 inicializado');
    
});