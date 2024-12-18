const PluginManager = window.PluginManager;
PluginManager.register('GpsrCanvas', () => import('./gpsr-canvas/gpsr-canvas.plugin'), '[data-gpsr-canvas]');