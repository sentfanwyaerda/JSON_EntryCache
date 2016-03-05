### JSON_EntryCache
*a JSON_Entry cache and schedular*

```php
$EC = new JSON_EntryCache('./cache.json');
$EC->import(file_get_contents('./test.json'));
$EC->save();
```
