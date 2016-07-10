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
php sim_analyser.phar -f file.sve [-o output_file [--xml-zipped|--xml-bz2] [--as-json|--as-csv] [--sjis]]
```
You can see analysed information via viewer.html

### simply
- from XML format
```
php sim_analyser.phar -f file.sve
```
- from XML zipped format
```
php sim_analyser.phar -f file.sve --xml-zipped
```
- from XML bz2 format
```
php sim_analyser.phar -f file.sve --xml-bz2
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