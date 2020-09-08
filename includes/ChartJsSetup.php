<?php
namespace ChartJs;

class ChartJsSetup {

	public static function onExtensionFunction() {
		// Semantic MediaWiki チェック
		if ( !defined( 'SMW_VERSION' ) ) {

			if ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) {
				die( "\nThe 'Chart.js' extension requires 'Semantic MediaWiki' to be installed and enabled.\n" );
			}

			die(
				'The Chart.js extension requires' .
				'<a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki">Semantic MediaWiki</a> to be ' .
				'installed and enabled.<br />'
			);
		}

		// フォーマットを登録する
		$GLOBALS['smwgResultFormats']['chartjs'] = ChartJsPrinter::class;
	}

}
