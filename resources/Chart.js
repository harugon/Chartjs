
( function ( mw ) {
	mw.loader.using( [ 'moment','ext.chart_js' ] ).done( function () {
		for( const chartjsId in window.chartjs ) {
			if( window.chartjs.hasOwnProperty(chartjsId) ) {
				const dataJson = window.chartjs[chartjsId];
				const pel = document.getElementById(chartjsId);
				const el = document.createElement( 'canvas' );
				el.setAttribute("id",chartjsId+'_cv');
				pel.append(el);
				new Chart(document.getElementById(chartjsId+'_cv'),dataJson)
			}
		}
	} )
}( mediaWiki ) );
