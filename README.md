
# 📈Chart.js for Semantic MediaWiki
 

Semantic MediaWikiでChart.jsを使用することができます

* [Chart\.js \| Open source HTML5 Charts for your website](https://www.chartjs.org/)
* [chartjs\-plugin\-colorschemes Color Chart](https://nagix.github.io/chartjs-plugin-colorschemes/colorchart.html)

## インストール



mediawikiがインストールされているディレクトリでcomposerでインストールします

    

"LocalSettings.php" に追記

    wfLoadExtension('chartjs');

## 使う
すべてのプロパティの型がnumberで有ることが期待されます。


### オプション

smwdocで表示することができます

```
{{#smwdoc: chartjs }}
```





## License



