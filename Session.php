<?php

namespace SessionMuscle;

use \InvalidArgumentException;
use \RuntimeException;
use SessionMuscle\Adapters\ISessionAdapter;

/**
 * Class Session.
 * @package SessionMuscle
 */
class Session extends GarbageCollector
{
    /**
     * Session cookie name.
     *
     * @var string
     */
    protected $cookieName = '';

    /**
     * Unique ID of current session
     * without session type.
     *
     * @var string
     */
    protected $sessID = '';

    /**
     * Store type of current session.
     *
     * @var string
     */
    protected $currentSessType = '';

    /**
     * Session type is array, which coordinates
     * the work of garbage collector and contains
     * two values: 'short' - short-term session
     * life, 'long' - long-term session life.
     * Session lifecycle determined by the
     * developer, using cookies parameters
     * and session settings.
     *
     * @var array
     */
    protected $sessTypes = [
        'short',
        'long'
    ];

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
     * @var string
     */
    protected $repository;

    /**
     * Session settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Session constructor.
     *
     * @param ISessionAdapter $adapter      Current adapter.
     * @param array $settings               Session and GC settigs.
     */
    public function __construct(ISessionAdapter $adapter, array $settings)
    {
        // store given settings
        $this->settings = $settings;

        // check session settings
        if (
        ! isset(
            $this->settings['cookieName'],
            $this->settings['sessLogName'],
            $this->settings['repository'],
            $this->settings['runRate'],
            $this->settings['short'],
            $this->settings['long']
            )
        ) {
            throw new RuntimeException(
                'Some settings are not found, please, refer to documentation');
        }

        // set session repository
        $this->repository = $this->settings['repository'];

        // set session cookie name
        $this->cookieName = $this->settings['cookieName'];

        // store and configure given session adapter
        $this->adapter = $adapter->configureAdapter($this->repository);

        // check whether we can read/write from/to given session repository
        if (! $this->adapter->checkAccess($this->repository)) {
            throw new RuntimeException(
                sprintf(
                    "Can't work with given session repository, specified as: %s.
                    Сheck connection or read/write permissions.", $this->repository
                )
            );
        }

        /**
         * Сheck the availability information about the session ID
         * in cookies and exists of specific session.
         */
        if (
            isset($_COOKIE[$this->cookieName])
            && $this->adapter->isExist($this->repository, $_COOKIE[$this->cookieName])
        ) {
            // On success, split cookie value
            // on session ID and session type
            $sessCookieParts = $this->splitSessCookie($_COOKIE[$this->cookieName], $this->sessTypes);
            // session ID without type
            $this->sessID = $sessCookieParts['id'];
            // session type without ID
            $this->currentSessType = $sessCookieParts['type'];
            // read and unserialize session data from session repository
            $this->sessStorage = $this->adapter->read($this->repository, $_COOKIE[$this->cookieName]);
        }
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
     * Adds new data to current session.
     *
     * @param string|integer $key   Data key.
     * @param mixed $value          Data.
     * @return bool                 On success.
     *
     * @throws InvalidArgumentException if given key not string or integer.
     */
    public function put($key, $value)
    {
        $this->checkKey($key);

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
     * Return current session ID with
     * session type (if exists).
     *
     * @return string   Current session ID
     *                  with type.
     */
    public function getSessID()
    {
        return $this->sessID;
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
     * This method return type of current session.
     *
     * @return string   Current session type
     *                  if set.
     */
    public function getSessionType()
    {
        return $this->currentSessType;
    }

    /**
     * This method set type of current session.
     *
     * @param string $sessionType   Current session type.
     * @return void
     * @throws InvalidArgumentException if method argument not a string,
     * if trying set invalid session type.
     */
    public function setSessionType($sessionType)
    {
        if (! is_string($sessionType)) {
            throw new InvalidArgumentException('Session type must be a string.');
        }

        if (! in_array($sessionType, $this->sessTypes)) {
            throw new InvalidArgumentException('Session type must be "short" or "long".');
        }

        $this->currentSessType = $sessionType;
    }

    /**
     * This method save session data. If old session exists,
     * save it data and send cookie. If no old session,
     * generate unique session ID, define session type, save
     * new session data and send cookie.
     * If no old session and sessionStorage empty do nothing.
     *
     * @return bool     True on success, false on failure.
     * @throws RuntimeException if can't save session.
     */
    public function save()
    {
        // run garbage collector
        $this->runGarbageCollector();

        // if current session not newly
        if ($this->isSavedSession()) {
            // get full session ID
            $fullSessID = $this->getFullSessID();
            // save session
            if ($this->adapter->save($this->repository, $fullSessID, $this->sessStorage)) {
                return setcookie($this->cookieName, $fullSessID, time() + (int) $this->settings[$this->currentSessType]);
            } else {
                throw new RuntimeException("Can't save session.");
            }
        }

        // if current session is newly
        if (! empty($this->sessStorage)) {
            // define session type
            $sessionType = ($this->currentSessType) ? $this->currentSessType : 'short';
            // generate new session ID and get full session ID
            $fullSessID = $this->generateSessID() . $sessionType;
            // save session
            if ($this->adapter->save($this->repository, $fullSessID, $this->sessStorage)) {
                return setcookie($this->cookieName, $fullSessID, time() + (int) $this->settings[$sessionType]);
            } else {
                throw new RuntimeException("Can't save session.");
            }
        }
    }

    /**
     * This method regenerate current session.
     *
     * @return bool     Return true on success or false
     *                  on failure.
     */
    public function regenerate()
    {
        // if session not newly - regenerate
        if ($this->isSavedSession()) {
            return $this->regenerateSavedSession();
        }

        // if this session newly - just generate new session ID
        return ($this->sessID = $this->generateSessID()) ? true : false;
    }

    /**
     * Remove all data from the session storage,
     * remove session essence and session cookie,
     * erase session ID and session type.
     *
     * @return bool     On success return true.
     */
    public function clear()
    {
        // remove session
        $this->adapter->erase($this->repository, $this->getFullSessID());

        // remove session cookie
        setcookie($this->cookieName, $this->getFullSessID(), time() - (int) $this->settings[$this->currentSessType]);

        // clear current sesson storage
        $this->sessStorage = [];

        // delete session ID and session type
        $this->sessID = '';
        $this->currentSessType = '';

        return true;
    }

    /**
     * This method check if session newly or not.
     *
     * @return bool     True if current session
     *                  not newly, false otherwise.
     */
    protected function isSavedSession()
    {
        return ($this->sessID !== '' && $this->currentSessType !== '') ? true : false;
    }

    /**
     * If exists unique sessin ID and session type,
     * this method return combined value, otherwise
     * this method returns empty string.
     *
     * @return string
     */
    protected function getFullSessID()
    {
        $sessID = $this->sessID;
        $sessType = $this->currentSessType;
        return (! empty($sessID) && ! empty($sessType)) ? $sessID . $sessType : '';
    }

    /**
     * This method regenerate saved session.
     * Namely remove old session cookie and old
     * session essence. Generate new session ID
     * and save session data in new session
     * essence.
     *
     * @return bool     Return true on success
     *                  or false on failure.
     */
    protected function regenerateSavedSession()
    {
        // remove old session essence
        $this->adapter->erase($this->repository, $this->getFullSessID());
        // remove old cookie
        setcookie($this->cookieName, $this->getFullSessID(), time() - (int) $this->settings[$this->currentSessType]);
        // generate new session ID
        $this->sessID = $this->generateSessID();
        // save session data to new session entity
        return $this->save();
    }

    /**
     * This method divide the contents
     * of the session cookie to session ID
     * and session type.
     *
     * @param string $cookieValue   Incoming cookie value.
     * @param array $sessTypes      Reserved cookies types.
     * @return array                Divided value of an array.
     */
    protected function splitSessCookie($cookieValue, array $sessTypes)
    {
        $sessIDParts = [];
        $typePos = $this->getSessTypePos($cookieValue, $sessTypes);
        $sessIDParts['id'] = substr($cookieValue, 0, $typePos);
        $sessIDParts['type'] = substr($cookieValue, $typePos);
        return $sessIDParts;
    }

    /**
     * Helper function which uses splitSessCookie()
     * method. Get session type position from full
     * session cookie value.
     *
     * @param string $fullSessID   Session cookie value.
     * @param array $sessTypes     Reserved cookies types.
     * @return integer             Position of session type if exist.
     */
    protected function getSessTypePos($fullSessID, array $sessTypes)
    {
        foreach ($sessTypes as $type) {
            $pos = strrpos($fullSessID, $type, -1);
            if ($pos) {
                return $pos;
            }
        }
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
}
