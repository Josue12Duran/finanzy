import './bootstrap';
import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';
import { FinanzyCharts } from './charts/index.js';
import { registrarDescargaRespaldo } from './ui/descarga-respaldo.js';
import { registrarEstadoDeCarga } from './ui/estado-carga.js';
import { registrarValidaciones } from './ui/validaciones.js';

registrarValidaciones();
registrarEstadoDeCarga();
registrarDescargaRespaldo();

window.bootstrap = bootstrap;
// Se mantiene para los gráficos creados a mano (p. ej. /demo).
window.Chart = Chart;
window.FinanzyCharts = FinanzyCharts;
