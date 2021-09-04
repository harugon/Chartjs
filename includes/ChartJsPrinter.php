<?php

namespace ChartJs;

use Html;
use ParamProcessor\ParameterTypes;
use ParamProcessor\ProcessedParam;
use SMW\Parser\RecursiveTextProcessor;
use SMW\Query\PrintRequest;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinter;
use SMWDataItem;
use SMWDataValue;
use SMWOutputs;
use SMWQuery;
use SMWRecordValue;
use SMWResultArray;

class ChartJsPrinter implements ResultPrinter {

	/**
	 * @var $id string
	 * @var $parameters ProcessedParam[]
	 */
	private $id;
	private $parameters;

	public function __construct() {
		$this->id = $this->newChartJsId();
	}

	/**
	 * 名前を返す
	 * @return string
	 */
	public function getName(): string {
		return wfMessage( 'chart-js-format-name' )->text();
	}

	/**
	 * パラメーター
	 * @param array $definitions
	 * @return array
	 *
	 * @see ResultPrinter::getParamDefinitions
	 */
	public function getParamDefinitions( array $definitions ) {

		$definitions['width'] = [
			'type' => ParameterTypes::DIMENSION,
			'message' => 'chart-js-op-width',
			'units' => [ 'px', 'ex', 'em', '%', '' ],
			'default' => '700px',
		];

        $definitions['charttitle'] = [
            'type' => ParameterTypes::STRING,
            'message' => 'chart-js-op-charttitle',
            'default' => "",
        ];

		$definitions['character_limit'] = [
			'type' => ParameterTypes::INTEGER,
			'message' => 'chart-js-op-character_limit',
			'default' => 10,
		];

		$definitions['stacked'] = [
			'type' => ParameterTypes::BOOLEAN,
			'message' => 'chart-js-op-stacked',
			'default' => false
		];

		$definitions['theme'] = [
			'type' => ParameterTypes::STRING,
			'message' => 'chart-js-op-theme',
			'default' => "tableau.ClassicColorBlind10"
		];

		//Chart Type
		$definitions['type'] = [
			'type' => ParameterTypes::STRING,
			'message' => 'chart-js-op-type',
			'default' => 'line',
			'values' => [ 'line','bar','horizontalBar','doughnut','pie','radar','polarArea' ],
		];

		//グルーピング
		$definitions['group'] = [
			'type' => ParameterTypes::STRING,
			'message' => 'chart-js-op-group',
			'default' => 'none',
			'values' => [ 'none','property', 'subject' ],
		];

		//凡例の位置
		$definitions['position'] = [
			'type' => ParameterTypes::STRING,
			'message' => 'chart-js-op-position',
			'default' => 'bottom',
			'values' => [ 'top', 'left', 'bottom', 'right' ],
		];

        $definitions['reverse'] = [
            'type' => ParameterTypes::BOOLEAN,
            'message' => 'chart-js-op-reverse',
            'default' => false
        ];

		return $definitions;
	}

	/**
	 * @param QueryResult $result
	 * @param ProcessedParam[] $parameters
	 * @param int $outputMode
	 *
	 * @return string
	 */
	public function getResult( QueryResult $result, array $parameters, $outputMode ): string {
		$this->parameters = $parameters;
		$chart_data = [];

		/**
		 * Get all SMWDIWikiPage objects that make up the results
		 * @var $subjects array ページ名all
		 * Get all print requests property labels
		 * @var $labels array プロパティ名all
		 */
		$subjects = $this->getSubjects( $result->getResults() );
		$labels = $this->getLabels( $result->getPrintRequests() );
		// $labels = array_column( $raw_labels, "label" );

		$group = $parameters['group']->getValue();
		$row_propertyLabel = [];
		$row = [];
		/**
		 * @var SMWResultArray [] | false $SMWResultArrays
		 */
		while ( $SMWResultArrays = $result->getNext() ) {

			/**
			 * @var SMWResultArray $SMWResultArrays
			 * @var SMWDataValue $SMWResultArray
			 */
			foreach ( $SMWResultArrays as $SMWResultArray ) {

				/** 　紐付いてるプロパティ
				 * @var $propertyLabel string プロパティ
				 */
				$propertyLabel = $SMWResultArray->getPrintRequest()->getLabel();
				$propertyKey = $SMWResultArray->getPrintRequest()->getCanonicalLabel();
				// Get the label for the current subject
				// getTitle()->getText() will return only the main text without the
				// fragment(#) which can be arbitrary in case subobjects are involved

				// getTitle()->getFullText() will return the text with the fragment(#)
				// which is important when using subobjects

				/** 　紐付いてるページ
				 * @var $subjectLabel  string ページ
				 */
				$subjectLabel = $SMWResultArray->getResultSubject()->getTitle()->getFullText();
				/**
				 * @var SMWDataValue|false $dataValue
				 */
				while ( ( $dataValue = $SMWResultArray->getNextDataValue() ) !== false ) {
					// プロパティに複数割り当てられている場合loop
					if ( $propertyLabel == '' ) {
						// メインラベル無視
						break;
					}

					if ( $dataValue->getDataItem()->getDIType() == SMWDataItem::TYPE_NUMBER ) {
					    //todo unit対応
						$number = $dataValue->getNumber();
						$row[$subjectLabel][$propertyKey] = $number;
					} else {
						$row_propertyLabel[$subjectLabel][$propertyKey] = $dataValue->getWikiValue();
					}
				}
			}
		}

		// ラベル　データ
		$chart_labels = [];
		if ( $group == 'property' ) {
			// 横軸 プロパティ名

			foreach ( $labels as  $Canonical => $Label ) {
				if ( $Label['type'] == '_num'||'_qty' ) { $chart_labels[] = $Label['label'];
				}
			}

			foreach ( $subjects as  $FullText => $Text ) {
				foreach ( $labels as  $Canonical => $Label ) {
					if ( $Label['type'] == '_num'||'_qty' ) {
						$chart_data[$FullText][] = $row[$FullText][$Canonical] ?? '';
					}
				}
			}
		} elseif ( !empty( $row_propertyLabel ) ) {
			// 横軸 指定プロパティの値
			$label_key = '';

			foreach ( $labels as  $Canonical => $Label ) {
				if ( !( $Label['type'] == '_num'||'_qty' ) ) {
					$label_key = $Canonical;
					break;
				}
			}

			foreach ( $subjects as  $FullText => $Text ) {
				$chart_labels[] = $row_propertyLabel[$FullText][$label_key] ?? '';

				foreach ( $labels as  $Canonical => $Label ) {
					if ( $Label['type'] == '_num'||'_qty' ) {
						$chart_data[$Canonical][] = $row[$FullText][$Canonical] ?? '';
					}
				}
			}
		} else {
			// 横軸 ページ名 $group=='subject'
			$chart_labels = array_values( $subjects );

			foreach ( $subjects as  $FullText => $Text ) {
				foreach ( $labels as  $Canonical => $Label ) {
					if ( $Label['type'] == '_num'||'_qty' ) {
						$chart_data[$Canonical][] = $row[$FullText][$Canonical] ?? '';
					}
				}
			}
		}

		// ラベル長さ制限
		$chart_labels = array_map( function ( $text ){ return $this->characterLimit( $text );
		}, $chart_labels );

		//datasets
		$chart_datasets = [];

		foreach ( $chart_data as  $key => $value ) {
			if ( $group == "property" ) {
				$dataset_label = $subjects[$key];
			} else {
				$dataset_label = $labels[$key]['label'];
			}

			$chart_datasets[] = [
				'label' => $this->characterLimit( $dataset_label ),
				'data' => $value
			];
		}

		// オプション
		$chart_options_scales = [];
		if ( in_array( $parameters['type']->getValue(), [ 'line','bar','horizontalBar' ,'polarArea'], true ) ) {
			$chart_options_scales = [
				// pie redr 消す
				"xAxes" => [ [
					"stacked" => ( $parameters['stacked']->getValue() ),
				] ],
				"yAxes" => [ [
					"ticks" => [
                        "reverse"=>$parameters['reverse']->getValue(),
						"beginAtZero" => true
					],
					"stacked" => ( $parameters['stacked']->getValue() )
				] ]
			];
		}

		$chart_json = [
			"type" => $parameters['type']->getValue(),
			"data" => [
				"labels" => $chart_labels,
				"datasets" => $chart_datasets,
			],
			"options" => [
			    "title"=>[
                    "display" =>($parameters['charttitle']->getValue()?true:false),
                    "text" =>$parameters['charttitle']->getValue()
                ],
				"scales" => $chart_options_scales,
				"legend" => [
					"position" => $parameters['position']->getValue(),
					],
				"plugins" => [
					"colorschemes" => [
						"scheme" => $parameters['theme']->getValue()
					]
				]
			],
			"maintainAspectRatio" => true
		];

		// リソースローダに追加
		SMWOutputs::requireResource( 'ext.chart_js' );
		// HeadItemに追加
		SMWOutputs::requireHeadItem(
			$this->id,
			$this->createJs( json_encode( $chart_json ) )
		);
		return $this->createHtml();
	}

	/**
	 * 　Data JSON
	 *
	 * @param string $json
	 * @return string
	 */
	private function createJs( $json ): string {
		return Html::rawElement(
			'script',
			[
				'type' => 'text/javascript'
			],
			"if (!window.hasOwnProperty('chartjs')) {window.chartjs = {};}"
			. "\n window.chartjs.{$this->id} = $json;"
		);
	}

	/**
	 * 描画HTML
	 *
	 * @return string
	 */
	private function createHtml(): string {
		// ローディング
		$processing = \SRFUtils::htmlProcessingElement();
		return Html::rawelement(
			'div',
			[
				'class' => 'chart_js_wrap',
				'style' => 'max-width:' . $this->parameters['width']->getValue() . ';',
			],
			Html::rawelement( 'div',
				[
					'id' => $this->id,
					'class' => 'chart_js_container',
					'style' => 'position: relative; width: 100%; height: 95%;',
				] )
		);
	}

	/**
	 * 連番？
	 *
	 * @return string
	 */
	private function newChartJsId(): string {
		static $chartNumber = 0;
		return 'chart_js_' . ++$chartNumber;
	}

	/**
	 * 文字数制限
	 * 鈴懸の木の道で「君の微笑みを夢に見る」と言ってしまったら僕たちの関係はどう変わってしまうのか、僕なりに何日か考えた上でのやや気恥ずかしい結論のようなもの
	 *
	 * @param string $txt
	 * @return string
	 */
	private function characterLimit( $txt ):string {
		return mb_substr( $txt, 0, $this->parameters['character_limit']->getValue() );
	}

	/**
	 * A quick getway method to find all SMWDIWikiPage objects that make up the
	 * results
	 * @param \SMW\DIWikiPage[] $result
	 * @return array
	 */
	private function getSubjects( $result ):array {
		$subjects = [];

		/**
		 * @var $wikiDIPage \SMWDIWikiPage
		 */
		foreach ( $result as $wikiDIPage ) {
			$subjects[$wikiDIPage->getTitle()->getFullText()] = $wikiDIPage->getTitle()->getText();
		}
		return $subjects;
	}

	/**
	 * Get all print requests property labels
	 * @param PrintRequest[] $result
	 * @return array
	 */
	private function getLabels( $result ):array {
		$printRequestsLabels = [];
		/**
		 * @var PrintRequest[]
		 * @var PrintRequest $printRequests
		 */
		foreach ( $result as $printRequests ) {
			if ( strlen( $printRequests->getLabel() ) ) {
				// $printRequestsLabels[]=$printRequests->getLabel();
				$printRequestsLabels[$printRequests->getCanonicalLabel()] = [
					'label' => $printRequests->getLabel(),
					'type' => $printRequests->getTypeID()
				];
			}
		}
		return $printRequestsLabels;
	}


	/**
	 * query mode
	 * @param \SMW\Query\QueryContext $context
	 *
	 * @return int
	 */
	public function getQueryMode( $context ): int {
		return SMWQuery::MODE_INSTANCES;
	}

	/**
	 * Set whether errors should be shown. By default they are.
	 *
	 * @param bool $show
	 */
	public function setShowErrors( $show ) {
	}

	/**
	 * Returns if the format is an export format.
	 *
	 * @return bool
	 */
	public function isExportFormat(): bool {
		return false;
	}

	/**
	 * @return string
	 */
	public function getDefaultSort(): string {
		return 'ASC';
	}

	/**
	 * @return bool
	 */
	public function isDeferrable(): bool {
		return false;
	}

	/**
	 * @return bool
	 */
	public function supportsRecursiveAnnotation(): bool {
		return false;
	}

	/**
	 * @param RecursiveTextProcessor $recursiveTextProcessor
	 */
	public function setRecursiveTextProcessor( RecursiveTextProcessor $recursiveTextProcessor ) {
	}

}
