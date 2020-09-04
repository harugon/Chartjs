<?php


namespace ChartJs;

use ParamProcessor\ParameterTypes;
use SMW\Parser\RecursiveTextProcessor;
use SMW\Query\PrintRequest;
use SMW\Query\ResultPrinter;
use SMWQueryResult;
use SMW\Query\QueryResult;
use SMWQuery;
use ParamProcessor\ProcessedParam;
use SMWDataItem;
use SMWDataValue;
use SMWRecordValue;
use SMWResultArray;
use SMWDIWikiPage;
use SMWOutputs;
use Html;

class ChartJsPrinter implements ResultPrinter
{

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
	 *
	 *
	 */
	public function getName(): string
	{
		return wfMessage('chart-js-format-name')->text();
	}

	/**
	 * パラメーター
	 * @param array $definitions
	 * @return array
	 *
	 * @see ResultPrinter::getParamDefinitions
	 */
	public function getParamDefinitions(array $definitions)
	{
		/**　幅
		 * '100%'
		 */
		$definitions['width'] = [
			'type' => ParameterTypes::DIMENSION,
			'message' => 'chart-js-op-width',
			'units' => [ 'px', 'ex', 'em', '%', '' ],
			'default' => '700px',
		];

		$definitions['character_limit'] = [
			'type' => ParameterTypes::INTEGER,
			'message' =>'chart-js-op-character_limit',
			'default' =>10,
		];

		$definitions['stacked'] = [
			'type' => ParameterTypes::BOOLEAN,
			'message' =>'chart-js-op-stacked',
			'default' => false
		];

		$definitions['theme'] = [
			'type' => ParameterTypes::STRING,
			'message' =>'chart-js-op-theme',
			'default' => "tableau.ClassicColorBlind10"
		];

		$definitions['type'] = [
			'type' => ParameterTypes::STRING,
			'message' =>'chart-js-op-type',
			'default' => 'line',
			'values' => ['line','bar','horizontalBar','doughnut','pie','radar'],
		];

		$definitions['group'] = [
			'type' => ParameterTypes::STRING,
			'message' =>'chart-js-op-group',
			'default' => 'none',
			'values' => ['none','property', 'subject'],
		];

		$definitions['position'] = [
			'type' => ParameterTypes::STRING,
			'message' =>'chart-js-op-position',
			'default' => 'bottom',
			'values' => ['top', 'left', 'bottom', 'right'],
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
	public function getResult( QueryResult $result, array $parameters, $outputMode): string
	{
		$this->parameters = $parameters;
		$chart_data = [];




		/**
		 * Get all SMWDIWikiPage objects that make up the results
		 * @var $subjects array ページ名all
		 * Get all print requests property labels
		 * @var $labels array プロパティ名all
		 */
		$subjects = $this->getSubjects( $result->getResults() );
		$raw_labels = $this->getLabels( $result->getPrintRequests() );
		$labels =array_column($raw_labels, "label");


		$group = $parameters['group']->getValue();
		$row_propertyLabel=[];
		$row = [];
		/**
		 * @var SMWResultArray [] | false $SMWResultArrays
		 */
		while ($SMWResultArrays = $result->getNext() ){

			/**
			 * @var SMWResultArray $SMWResultArrays
			 * @var SMWDataValue $SMWResultArray
			 */
			foreach ($SMWResultArrays as $SMWResultArray){

				/**　紐付いてるプロパティ
				 * @var $propertyLabel string プロパティ
				 */
				$propertyLabel = $SMWResultArray->getPrintRequest()->getLabel();
				// Get the label for the current subject
				// getTitle()->getText() will return only the main text without the
				// fragment(#) which can be arbitrary in case subobjects are involved

				// getTitle()->getFullText() will return the text with the fragment(#)
				// which is important when using subobjects

				/**　紐付いてるページ
				 * @var $subjectLabel  string ページ
				 */
				$subjectLabel = $SMWResultArray->getResultSubject()->getTitle()->getFullText();

				/**
				 * @var SMWDataValue|false $dataValue
				 */
				while ( ($dataValue = $SMWResultArray->getNextDataValue() ) !== false ) {
					//プロパティに複数割り当てられている場合loop
					if ($propertyLabel=='') break;//メインラベル無視

					/**
					 * Semantic MediaWiki and related extensions: SMWRecordValue
					 * https://doc.semantic-mediawiki.org/classSMWRecordValue.html
					 *
					 * @var SMWRecordValue $dataValue
					 */
					if ( $dataValue->getDataItem()->getDIType() == SMWDataItem::TYPE_NUMBER ) {
						$number =$dataValue->getNumber();
						$row[$subjectLabel][$propertyLabel]=$number;
					}else{
						$row_propertyLabel[$subjectLabel][$propertyLabel] = $dataValue->getWikiValue();
					}
				}
			}
		}




		//ラベル　データ
		if($group=='property'){
			//横軸 プロパティ名
			$chart_labels=[];
			foreach ($raw_labels as $value1){
				if ($value1['type'] == '_num') $chart_labels[]= $value1['label'];
			}
			foreach ($subjects as $value){
				foreach ($raw_labels as $value2){
					if ($value2['type']=='_num') $chart_data[$value][]=$row[$value][$value2['label']]??'';
				}
			}
		}elseif(!empty($row_propertyLabel)){
			//横軸 指定プロパティの値
			$label_name = '';
			foreach ($raw_labels as $value1 ){
				if (!($value1['type'] == '_num')){
					$label_name = $value1['label'];
					break;
				}
			}

			$chart_labels = [];
			foreach ($subjects as $value) {
				$label = $row_propertyLabel[$value][$label_name]?? '';
				$chart_labels[]= $label;

				foreach ($raw_labels as $value2) {
					if ($value2['type'] == '_num') $chart_data[$value2['label']][] = $row[$value][$value2['label']] ?? '';
				}
			}
		}else {
			//横軸 ページ名 $group=='subject'
			$chart_labels = $subjects;
			foreach ($subjects as $value) {
				foreach ($raw_labels as $value2) {
					if ($value2['type'] == '_num') $chart_data[$value2['label']][] = $row[$value][$value2['label']] ?? '';
				}
			}
		}

		//ラベル長さ制限
		$chart_labels = array_map(function ($text){return $this->characterLimit($text);},$chart_labels);


		//Data Set
		$chart_datasets=[];
		foreach ($chart_data as  $key => $value){
			$chart_datasets[]=[
				'label'=> $this->characterLimit($key),
				'data'=>$value
			];
		}


		//オプション
		$chart_options_scales =[];
		if(in_array($parameters['type']->getValue(),['line','bar','horizontalBar'], true)){
			$chart_options_scales =[
				//pie redr 消す
				"xAxes"=>[[
					"stacked"=>($parameters['stacked']->getValue())
				]],
				"yAxes"=>[[
					"ticks"=>[
						"beginAtZero"=>true
					],
					"stacked"=>($parameters['stacked']->getValue())
				]]
			];
		}

		$chart_json =[
			"type"=> $parameters['type']->getValue(),
			"data"=>[
				"labels"=>$chart_labels,
				"datasets"=>$chart_datasets,
			],
			"options"=>[
				"scales"=>$chart_options_scales,
				"legend"=>[
					"position"=>$parameters['position']->getValue(),
					],
				"plugins"=>[
					"colorschemes"=>[
						"scheme"=>$parameters['theme']->getValue()
					]
				]
			],
			"maintainAspectRatio"=>true
		];

		//リソースローダに追加
		SMWOutputs::requireResource( 'ext.chart_js' );
		//HeadItemに追加
		SMWOutputs::requireHeadItem(
			$this->id,
			$this->createJs(json_encode($chart_json))
		);
		return $this->createHtml();
	}


	/**
	 *　Data JSON
	 *
	 * @return string $json
	 */
	private function createJs($json): string {
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
		//ローディング
		$processing = \SRFUtils::htmlProcessingElement();
		return Html::rawelement(
			'div',
			[
				'class' => 'chart_js_wrap',
				'style' => 'max-width:'.$this->parameters['width']->getValue().';',
			],
			Html::rawelement('div',
				[
					'id' => $this->id,
					'class' => 'chart_js_container',
					'style' => 'position: relative; width: 100%; height: 95%;',
				])
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
	 * @param $txt string
	 * @return string
	 */
	private function characterLimit($txt):string {
		return mb_substr($txt,0,$this->parameters['character_limit']->getValue());
	}

	/**
	 * A quick getway method to find all SMWDIWikiPage objects that make up the
	 * results
	 * @param
	 * @return array
	 */
	private function getSubjects( $result ):array {
		$subjects = [];

		/**
		 * @var
		 * @var $wikiDIPage SMWDIWikiPage
		 */
		foreach ( $result as $wikiDIPage ) {
			$subjects[] = $wikiDIPage->getTitle()->getText();
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
			if (strlen($printRequests->getLabel())){
				//$printRequestsLabels[]=$printRequests->getLabel();
				$printRequestsLabels[]=[
					'label'=>$printRequests->getLabel(),
					'type'=>$printRequests->getTypeID()
				] ;
			}
		}
		return $printRequestsLabels;
	}

	/**
	 * Get a single data value item
	 * @param int $type SMWDataItem
	 * @param SMWDataValue $dataValue
	 *
	 * @return mixed
	 */
	private function getDataValueItem($type,SMWDataValue $dataValue ):string {
		// For all other data types return the wikivalue
		return $dataValue->getWikiValue();
	}

	public function getQueryMode($context): int
	{
		return SMWQuery::MODE_INSTANCES;
	}

	public function setShowErrors($show)
	{
	}

	public function isExportFormat(): bool
	{
		return false;
	}

	public function getDefaultSort(): string
	{
		return 'ASC';
	}

	public function isDeferrable(): bool
	{
		return false;
	}

	public function supportsRecursiveAnnotation(): bool
	{
		return false;
	}

	public function setRecursiveTextProcessor(RecursiveTextProcessor $recursiveTextProcessor)
	{
	}

}
