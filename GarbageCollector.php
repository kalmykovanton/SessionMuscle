<?php

namespace SessionMuscle;

use \InvalidArgumentException;

/**
 * Class GarbageCollector.
 * @package Jazz\Application\SessionHandle
 */
class GarbageCollector
{
    /**
     * Session and GC settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $sessLogName = '.sesslog';

    /**
     * @var array
     */
    protected $sessLogOptions = [
        'starts' => 0
    ];

    /**
     * @var array
     */
    protected $sessLogData = [];

    protected function runGarbageCollector()
    {
        // if session log file not exist create it
        $this->checkSessionLog();
        // get session log data
        $this->sessLogData = $this->adapter->read($this->repository, $this->sessLogName);
        // increasing sessions counter
        $this->sessLogData['starts'] += 1;
        // check sessions start counter
        if ($this->checkSessionsCounter()) {
            // collect garbage
            $this->collectGarbage();
        }
        // save session log data
        $this->adapter->save($this->repository, $this->sessLogName, $this->sessLogData);
    }

    /**
     *
     */
    protected function checkSessionLog()
    {
        if (! $this->adapter->isExist($this->repository, $this->sessLogName)) {
            $this->adapter->save($this->repository, $this->sessLogName, $this->sessLogOptions);
        }
    }

    /**
     * @return bool
     */
    protected function checkSessionsCounter()
    {
        return ($this->sessLogData['starts'] >= $this->settings['runRate']) ? true : false;
    }

    /**
     *
     */
    protected function collectGarbage()
    {
        $this->adapter->collectGarbage($this->getSessionSettings(), $this->getSessionTypes());
        $this->sessLogData['starts'] = 0;
    }

    /**
     * @param $key
     * @return mixed|string
     */
    protected function getDataFromLog($key)
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException("Session's log data key must be a string.");
        }

        return (array_key_exists($key, $this->sessLogData)) ? $this->sessLogData[$key] : '';
    }
}

