# OpenPHPTorCurl

It's a class to call URL via Tor

# Example

```php
$browser = new OpenPHPTorCurl\Browser();
$browser->setUrl('http://www.example.com')
        ->add('first_param', 'value')
        ->addFile('file', '/home/my-file.txt')
        ->setUserAgent('firefox');
if ($browser->post() === false) {
  $browser->getError();
} else {
  $browser->getStatusCode();
  $browser->getHeaders();
  $browser->getContent();
}
```

# Todo

* allow to use cookies
* allow to use custom HTTP method
* check if Tor run
* method to check your IP address
* SSL support

# Caution

Tor must run when you call script

# License

[Do What The Fuck You Want To Public License](http://sam.zoy.org/wtfpl/COPYING)