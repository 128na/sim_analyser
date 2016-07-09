# Simutrans save data analyzer

## Description
Simutransのセーブデータから路線情報を取得するCLIツールです。

## Format
- XML

## Dependencies
- PHP 5.6~

## Usage
default output type is jsonp file.
```
php sim_analyzer.phar -f file.sve [-o output_file [--as-json|--as-csv --sjis]]
```
You can see analysed information via viewer.html

### simply
```
php sim_analyzer.phar -f file.sve
```

### export as json file
```
php sim_analyzer.phar -f file.sve -o output_file.json --as-json
```

### export as csv
- for Linux, Mac(UTF-8 format)
```
php sim_analyzer.phar -f file.sve --as-csv
```

- for Windows(SJIS format)
```
php sim_analyzer.phar -f file.sve --as-csv --sjis
```
