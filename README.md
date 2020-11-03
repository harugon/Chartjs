Chartjs
====
 ![demo](https://raw.githubusercontent.com/harugon/Chartjs/master/.github/screenshots/Chartjs.png)
 
📈Chart.js for Semantic MediaWiki


## Description
Semantic MediaWiki 意味的検索 でChart.jsを使用することができます

## Download

### Composer
Composer でインストールします [composer.local.json](https://www.mediawiki.org/wiki/Composer#Using_composer-merge-plugin)
```bash
COMPOSER=composer.local.json composer require harugon/Chartjs
```

## Install


LocalSettings.php に下記を追記
```php
wfLoadExtension( 'Chartjs' );
```

extensions/Chartjs　ディレクトリに移動し
```bash
composer install --no-dev
```

[wikimedia/composer\-merge\-plugin](https://github.com/wikimedia/composer-merge-plugin)で追記する方法もあり



## Library
* [Chart\.js \| Open source HTML5 Charts for your website](https://www.chartjs.org/)
* [chartjs\-plugin\-colorschemes Color Chart](https://nagix.github.io/chartjs-plugin-colorschemes/colorchart.html)



## Licence

MIT

## Author

[harugon](https://github.com/harugon)

# 
