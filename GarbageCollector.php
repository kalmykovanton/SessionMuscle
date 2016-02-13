<?php

namespace SessionMuscle;

use \InvalidArgumentException;

/**
 * Class GarbageCollector.
 * @package SessionMuscle
 */
abstract class GarbageCollector
{
    /**
     * Session service settings.
     *
     * @var array
     */
    protected $sessLogOptions = [
        // stores information about
        // how many times the sessions
        // are launched
        'starts' => 0
    ];

    /**
     * Session service settings data.
     *
     * @var array
     */
    protected $sessLogData = [];

    /**
     * This method runs the garbage collector
     * of obsolete sessions and write session logs.
     *
     * @return bool  True on success or false
     *               on failure.
     */
    protected function runGarbageCollector()
    {
        // if session log essence does not exist create it
        $this->checkSessionLog();
        // get session log data
        $this->sessLogData = $this->adapter->read($this->repository, $this->settings['sessLogName']);
        // increasing sessions counter
        $this->sessLogData['starts'] += 1;
        // check sessions start counter
        if ($this->checkSessionsCounter()) {
            // collect garbage
            $this->collectGarbage();
        }
        // save session log data
        return $this->adapter->save($this->repository, $this->settings['sessLogName'], $this->sessLogData);
    }

    /**
     * This method checks if exists the essence for recording
     * sessions logs, if not then create one.
     */
    protected function checkSessionLog()
    {
        if (! $this->adapter->isExist($this->repository, $this->settings['sessLogName'])) {
            $this->adapter->save($this->repository, $this->settings['sessLogName'], $this->sessLogOptions);
        }
    }

    /**
     * Checks session's counter.
     *
     * @return bool
     */
    protected function checkSessionsCounter()
    {
        return ($this->sessLogData['starts'] >= $this->settings['runRate']) ? true : false;
    }

    /**
     *  Run garbage collector of current adapter.
     */
    protected function collectGarbage()
    {
        $this->sessLogData['starts'] = 0;
        $this->adapter->collectGarbage($this->getSessionSettings(), $this->getSessionTypes());
    }
}
