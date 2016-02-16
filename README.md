# SessionMuscle
Session provider component for ITCourses Jazz Framework
## How to use:
Every time when there is a request to the server, SessionMuscle will start session automatically. To modify or save current 
session data use SessionMuscle API methods. Under session type understood long-term session or short-term session. By default,
all sessions are short-term. The duration of the sessions, you can specify in the session settings. Depending of the session
type, session essentially will end on ***short*** or ***long*** postfix. For example:

63073ae80b786fdc21bb7616a54d25615e6c8f28**short** - name of short session

or 27s45ae80b987fsc22bs4576v54d25615w6c8f28**long** - name of long session

Garbage collector will start automatically, depending of 'runRate' session setting and session lifetime types.

**NOTE:** session repository must have read/write permissions.  

First of all, create an array of settings for SessionMuscle component:
```php
// settings array
$settings = [
    // cookie name which will store the session id
    'cookieName' => 'sess',
    // session entity to record logs
    'sessLogName' => '.sesslog',
    // session repository in this case be folder,
    // generally it can be any
    'repository' => 'storage/sessions',
    // through a gap to run the garbage collector
    'runRate' => 10,
    // short session lifetime in seconds
    'short' => 60,
    // long session lifetime in seconds
    'long' => 120
];
```
Then, create adapter which will be responsible for maintaining sessions (in this example, this is adapter who stored sessions into files):
```php
use SessionMuscle\Adapters\FileAdapter;
$adapter = new FileAdapter();
```
And now you can create session instance, first argument is session adapter instance, second - session settings:
```php
use SessionMuscle\Session;
$session = new Session($adapter, $settings);
```
## SessionMuscle API
Retrieve all data from the session:
```php
$session->all(); // return array of session data
```
Retrieve a value from the session. 
You may also pass a default value as the second argument to this method. 
This default value will be returned if the specified key does not exist in the session:
```php
$session->get($key, $default = ''); // return session data or default value
```
Add new data to current session:
```php
$session->put($key, $value); // return true on success
```
Check if an item exists in the session:
```php
$session->has($key); // return true if key exists or false if no
```
Retrieve a value from the session, and then delete it.
You may also pass a default value as the second argument to this method. 
This default value will be returned if the specified key does not exist in the session:
```php
$session->pull($key, $default = ''); // return session data or default value.
```
Remove a piece of data from the session by given key:
```php
$session->delete($key, $default = ''); // true on successful removal or false if no
```
To get current session ID with session type (if exists) use:
```php
$session->getSessID(); // current session ID with session type
```
To get current repository use:
```php
$session->getRepository();
```
To get current session cookie name use:
```php
$session->getCookieName();
```
To get used session types use:
```php
$session->getSessionTypes(); // return array of session types
```
To get current session type use:
```php
$session->getSessionType(); // return current session type
```
To set type of current session use:
```php
$session->setSessionType($sessionType);
```
To get current session settings use:
```php
$session->getSessionSettings(); // return current session settings array
```
To save current session use:
```php
$session->save();
```
To regenerate current session use:
```php
$session->regenerate();
```
To completely remove current session use:
```php
$session->clear();
```
