# Simutrans save data analyzer

## Description
Simutransのセーブデータから路線情報を取得するCLIツールです。

## Format
- XML

## Dependencies
- PHP 5.4

## Usage
default output type is jsonp file.
```
sim_analyzer.phar -f file.sve [-o output_file [--as-json]]
```

### simply
```
php sim_analyzer.phar -f file.sve
```
and open viewer.html

### export as json file
```
php sim_analyzer.phar -f file.sve -o output_file.json --as-json
```
