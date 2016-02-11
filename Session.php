<?php

namespace Jazz\Application\SessionHandle;

use \InvalidArgumentException;
use \RuntimeException;
use Jazz\Application\SessionHandle\Adapters\ISessionAdapter;

/**
 * Class Session.
 * @package Jazz\Application\SessionHandle
 */
class Session extends GarbageCollector
{
    /**
     * Session cookie name.
     *
     * @var string
     */
    protected $cookieName = 'sess';

    /**
     * Unique ID of current session
     * without session type.
     *
     * @var string
     */
    protected $sessID = '';

    /**
     * Unique ID of current session
     * with session type.
     *
     * @var string
     */
    protected $sessIDWithType = '';

    /**
     * Temporary storage of the
     * current session data.
     *
     * @var array
     */
    protected $sessStorage = [];

    /**
     * Store adapter that is used
     * to save the session data.
     *
     * @var object
     */
    protected $adapter;

    /**
     * Repository which contains
     * session data.
     *
     * @var string|resource
     */
    protected $repository;

    /**
     * Session type is array, which contains
     * two values: 'short' - short-term session
     * life, 'long' - long-term session life.
     * Session lifecycle determined by the
     * developer, using cookies parameters.
     *
     * @var array
     */
    protected $sessTypes = [
        'short',
        'long'
    ];

    /**
     * Store session lifetime flag.
     * By default session lifetime
     * is short. Change this parameter
     * with setLifetime method.
     *
     * @var string
     */
    protected $sessLifetime = 'short';

    /**
     * Session constructor.
     *
     * @param ISessionAdapter $adapter      Current adapter.
     * @param array $settings               Session and GC settigs.
     */
    public function __construct(ISessionAdapter $adapter, $settings = [])
    {
        // check if valid type of given setting
        if (! is_array($settings)) {
            throw new InvalidArgumentException('Session settings must be an array.');
        }

        // store given adapter
        $this->adapter = $adapter;

        // store given settings (if exist)
        $this->settings = $settings;

        // store given repository
        $this->repository = $this->settings['repository'];

        // check whether we can read/write from/to given session repository
        if (! $this->adapter->checkAccess($this->repository)) {
            throw new RuntimeException(
                sprintf(
                    "Can't work with given session repository, specified as: %s.
                    Сheck connection or read/write permissions.", $this->repository
                )
            );
        }

        // check valid garbage collector settings
        if (
            isset($this->settings['runRate'],
            $this->settings['short'],
            $this->settings['long'])
        ) {
            // run garbage collector
            $this->runGarbageCollector();
        }

        /**
         * Сheck the availability information about the session ID
         * in cookies and exists of specific session.
         */
        if (
            isset($_COOKIE[$this->cookieName])
            && $this->adapter->isExist($this->repository, $_COOKIE[$this->cookieName])
        ) {
            // On success store session id
            $this->sessIDWithType = $_COOKIE[$this->cookieName];
            // read and unserialize session data from session repository
            $this->sessStorage = $this->adapter->read($this->repository, $this->sessIDWithType);
        } else {
            /**
             * If there is no ID in cookies or session repository item
             * does not exist, create new session ID.
            */
            $this->sessID = $this->generateSessID();
        }
    }

    /**
     * Set session lifetime flag.
     * 'short' equals short-term session
     * life, 'long' equals long-term session life.
     * Session lifecycle determined by the
     * developer, using cookies parameters.
     *
     * @param string $lifetime      Сorrect session
     *                              lifetime flag.
     * @return false                On failure.
     */
    public function setLifetime($lifetime)
    {
        if (! is_string($lifetime)) {
            throw new InvalidArgumentException('Session lifetime parameter must be a string.');
        }

        return (in_array($lifetime, $this->sessTypes)) ? $this->sessLifetime = $lifetime : false;
    }

    /**
     * Return session lifetime flag.
     *
     * @return string   Session lifetime flag.
     */
    public function getLifetime()
    {
        return $this->sessLifetime;
    }

    /**
     * Retrieve all data from the session.
     *
     * @return array    Session data.
     */
    public function all()
    {
        return $this->sessStorage;
    }

    /**
     * Retrieve a value from the session. You may also pass a default value
     * as the second argument to this method. This default value
     * will be returned if the specified key does not exist in the session.
     *
     * @param string|integer $key   Data key.
     * @param mixed $default        Default value.
     * @return mixed                Session data or default value.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    public function get($key, $default = '')
    {
        $this->checkKey($key);

        if ($this->has($key)) {
            return $this->sessStorage[$key];
        }

        return $default;
    }

    /**
     * Update session data for a given key.
     *
     * @param string|integer $key   Data key.
     * @param mixed $value          New session data.
     * @return bool                 True on success update or false if no.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    public function update($key, $value)
    {
        $this->checkKey($key);

        if ($this->has($key)) {
            $this->sessStorage[$key] = $value;
            return true;
        }

        return false;
    }

    /**
     * Adds new data to current session.
     *
     * @param string|integer $key   Data key.
     * @param mixed $value          Data.
     * @return bool                 On success.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     * @throws RuntimeException if given key already exists.
     */
    public function put($key, $value)
    {
        $this->checkKey($key);

        if ($this->has($key)) {
            throw new RuntimeException(
                "Can't add new data. Given key already exists. Use the update method for rewrite data."
            );
        }

        $this->sessStorage[$key] = $value;
        return true;
    }

    /**
     * This method check if an item exists in the session.
     *
     * @param string|integer $key   Data key.
     * @return bool                 True if key exists or false if no.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    public function has($key)
    {
        $this->checkKey($key);

        if (array_key_exists($key, $this->sessStorage)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve a value from the session, then delete it.
     * You may also pass a default value as the second argument
     * to this method. This default value will be returned
     * if the specified key does not exist in the session.
     *
     * @param string|integer $key   Data key.
     * @param mixed $default        Default value.
     * @return mixed                Session data or default value.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    public function pull($key, $default = '')
    {
        $this->checkKey($key);

        $itemData = $this->get($key, $default);
        $this->delete($key);
        return $itemData;
    }

    /**
     * Remove a piece of data from the session by given key.
     *
     * @param string|integer $key   Data key.
     * @return bool                 True on successful removal or false if no.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    public function delete($key)
    {
        $this->checkKey($key);

        if ($this->has($key)) {
            unset($this->sessStorage[$key]);
            return true;
        }

        return false;
    }

    /**
     * Remove all data from the session storage
     * and delete session item in session
     * repository.
     *
     * @return bool     On success return true.
     */
    public function clear()
    {
        // clear current sesson storage if exists
        if (! empty($this->sessStorage)) {
            $this->sessStorage = [];
        }

        // delete session item from session repository
        // if exists
        if ($this->sessIDWithType !== '') {
            $this->adapter->erase($this->repository, $this->sessIDWithType);
            $this->sessIDWithType = '';
        }

        // remove session ID without session type
        // if exists
        if ($this->sessID !== '') {
            $this->sessID = '';
        }

        return true;
    }

    /**
     * Return current session ID with
     * session type (if exists).
     *
     * @return string   Current session ID
     *                  with type.
     */
    public function getSessID()
    {
        return $this->sessIDWithType;
    }

    /**
     * Return current repository.
     *
     * @return string|resource  Current repository.
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Return session cookie name.
     *
     * @return string   Cookie name.
     */
    public function getCookieName()
    {
        return $this->cookieName;
    }

    /**
     * Return array with session types.
     *
     * @return array    Array with session
     *                  types.
     */
    public function getSessionTypes()
    {
        return $this->sessTypes;
    }

    /**
     * Return array with session settings.
     *
     * @return array    Session settings.
     */
    public function getSessionSettings()
    {
        return $this->settings;
    }

    /**
     * This method regenerate current session.
     *
     * @return bool     Return true on success or false
     *                  on failure.
     */
    public function regenerate()
    {
        // if saved session exists - regenerate
        if ($this->sessIDWithType !== '') {
            return $this->regenerateSavedSession();
        }

        // if new session - just generate new session ID
        // whitout session type
        $this->sessID = $this->generateSessID();
        return ($this->sessID) ? true : false;
    }

    /**
     * Save session data.
     * If session storage not empty, session data will
     * be saved in current session repository. Otherwise,
     * do nothing.
     *
     * @return bool
     */
    public function save()
    {
        $successFlag = true;

        if ($this->sessIDWithType === '') {
            $this->sessIDWithType = $this->sessID . $this->sessLifetime;
        }

        if (! empty($this->sessStorage)) {
            $successFlag = $this->adapter->save($this->repository, $this->sessIDWithType, $this->sessStorage);
        }

        return ($successFlag) ? true : false;
    }

    /**
     * This method generate new session id with old
     * session type and save session data to new
     * session essence.
     *
     * @return bool     Return true on success
     *                  or false on failure.
     */
    protected function regenerateSavedSession()
    {
        // cached session type
        $cachedSessType = substr($this->sessIDWithType, $this->getSessTypePos());
        // remove old session file
        $this->adapter->erase($this->repository, $this->sessIDWithType);
        // generate new session ID with session type
        $this->sessIDWithType = $this->generateSessID() . $cachedSessType;
        // save session data to new session file
        return $this->adapter->save($this->repository, $this->sessIDWithType, $this->sessStorage);
    }

    /**
     * Generate a new unique session ID
     * by calculates the sha1 hash using
     * uniqid PHP native function.
     * @see http://php.net/manual/en/function.sha1.php
     * @see http://php.net/manual/en/function.uniqid.php
     *
     * @return string   Unique session ID.
     */
    protected function generateSessID()
    {
        return sha1(uniqid('', true));
    }

    /**
     * Сhecks whether the key a string or a number.
     *
     * @param string|integer $key   Data key.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    protected function checkKey($key)
    {
        if (! is_string($key) && ! is_integer($key)) {
            throw new InvalidArgumentException("Given key must be a string or integer.");
        }
    }

    /**
     * Helper function which uses regenerateSavedSession()
     * method. Get session type position in session ID.
     *
     * @return integer  Session type position.
     */
    protected function getSessTypePos()
    {
        foreach ($this->sessTypes as $type) {
            $pos = strrpos($this->sessIDWithType, $type, -1);
            if ($pos !== 0) {
                return $pos;
            }
        }
    }
}
