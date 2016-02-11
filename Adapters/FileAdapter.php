<?php

namespace SessionMuscle\Adapters;

use \InvalidArgumentException;
use \FilesystemIterator;

/**
 * Class FileAdapter.
 * @package Jazz\Application\SessionHandle\Adapters
 */
class FileAdapter implements ISessionAdapter
{
    /**
     * @var object
     */
    public $fileIterator;

    public function __construct($path, $flag)
    {
        $this->fileIterator = new FilesystemIterator($path, $flag);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @return bool
     */
    public function checkAccess($repository)
    {
        return (is_readable($repository) && is_writable($repository)) ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @param string $sessID
     * @return bool
     */
    public function isExist($repository, $sessIDWithType)
    {
        $this->checkRepoNameAndSessID($repository, $sessIDWithType);

        return (file_exists($this->buidPathToFile($repository, $sessIDWithType))) ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @param string $sessIDWithType
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function read($repository, $sessIDWithType)
    {
        $this->checkRepoNameAndSessID($repository, $sessIDWithType);

        return unserialize(
            file_get_contents($this->buidPathToFile($repository, $sessIDWithType))
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @param string $sessIDWithType
     * @param array $sessionData
     * @return bool
     */
    public function save($repository, $sessIDWithType, $sessionData)
    {
        $this->checkRepoNameAndSessID($repository, $sessIDWithType);

        $fullPathToFile = $this->buidPathToFile($repository, $sessIDWithType);

        return (file_put_contents($fullPathToFile, serialize($sessionData))) ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @param string $sessIDWithType
     * @return bool
     */
    public function erase($repository, $sessIDWithType)
    {
        $this->checkRepoNameAndSessID($repository, $sessIDWithType);

        $fullPathToFile = $this->buidPathToFile($repository, $sessIDWithType);

        return (file_exists($fullPathToFile)) ? unlink($fullPathToFile) : false;
    }

    /**
     * @param $sessionSettings
     * @param $sessionTypes
     * @return void
     */
    public function collectGarbage($sessionSettings, $sessionTypes)
    {
        if (! is_array($sessionSettings)) {
            throw new InvalidArgumentException("Session settings must be an array.");
        }

        if (! is_array($sessionTypes)) {
            throw new InvalidArgumentException("Session types must be an array.");
        }

        foreach ($this->fileIterator as $file) {
            // get current filename
            $filename = $file->getFilename();
            // check is not hidden the current file
            if (strrpos($filename, '.') === 0) {
                continue;
            }
            // get session type
            $sessionType = $this->getSessionType($filename, $sessionTypes);
            // check if session type present in session settings
            if (array_key_exists($sessionType, $sessionSettings)) {
                // get current Unix timestamp
                $currentTime = time();
                // get last modification time
                // of current file (Unix timestamp)
                $lastModified = $file->getMTime();
                // get file lifetime
                $fileLifetime = $sessionSettings[$sessionType];
                // if expired
                if ($currentTime - $lastModified >= $fileLifetime) {
                    // remove file
                    unlink($file);
                }
            }
        }
    }

    /**
     * @param $filename
     * @param $sessionTypes
     * @return string
     */
    protected function getSessionType($filename, $sessionTypes)
    {
        $pos = 0;
        foreach ($sessionTypes as $type) {
            $pos = strrpos($filename, $type, -1);
            if ($pos !== 0) {
                break;
            }
        }

        if ($pos !== 0) {
            return substr($filename, $pos);
        }

        return '';
    }

    /**
     * Check whether the repository name
     * or session identifier are strings.
     *
     * @param string $repoName     Session repository name.
     * @param string $sessID       Session identifier.
     * @throws InvalidArgumentException if session repository or session ID not strings.
     */
    protected function checkRepoNameAndSessID($repoName, $sessID)
    {
        if (! is_string($repoName)) {
            throw new InvalidArgumentException('Session repository name must be a string.');
        }

        if (! is_string($sessID)) {
            throw new InvalidArgumentException('Session identifier must be a string.');
        }
    }

    /**
     * This method buld full path to current session file.
     *
     * @param string $repository        Folder where to save session files.
     * @param string $sessIDWithType    Session ID with session type.
     * @return string                   Full path to file.
     */
    protected function buidPathToFile($repository, $sessIDWithType)
    {
        return rtrim($repository, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sessIDWithType;
    }
}
