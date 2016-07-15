# Simutrans save data analyser

## Description
Simutransのセーブデータから路線情報を取得するCLIツールです。

## Format
- xml
- xml(zipped)
- xml(bz2)

## Dependencies
- PHP 5.6~

## Usage
default output type is jsonp file.
```
php sim_analyser.phar -f file.sve [-o output_file [--as-json|--as-csv] [--sjis]]
```
You can see analysed information via viewer.html

### simply
```
php sim_analyser.phar -f file.sve
```

### set export filename
```
php sim_analyser.phar -f file.sve -o output.jsonp
```


### export as json
```
php sim_analyser.phar -f file.sve --as-json
```

### export as csv
- for Linux, Mac(UTF-8 format)
```
php sim_analyser.phar -f file.sve --as-csv
```
- for Windows(SJIS format)
```
php sim_analyser.phar -f file.sve --as-csv --sjis
```

## License
WTFPL (http://www.wtfpl.net/txt/copying/)