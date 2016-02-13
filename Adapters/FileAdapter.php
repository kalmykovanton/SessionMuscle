<?php

namespace SessionMuscle\Adapters;

use \InvalidArgumentException;
use \FilesystemIterator;

/**
 * Class FileAdapter.
 * @package SessionMuscle\Adapters
 */
class FileAdapter implements ISessionAdapter
{
    /**
     * Instance if PHP's native
     * FilesystemIterator
     *
     * @var object
     */
    public $fileIterator;

    /**
     * {@inheritdoc}
     *
     * @param string $path
     * @return FileAdapter instance $this
     */
    public function configureAdapter($path)
    {
        $this->fileIterator = new FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
        return $this;
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
    public function isExist($repository, $fullSessID)
    {
        $this->checkRepoNameAndSessID($repository, $fullSessID);

        return (file_exists($this->buidPathToFile($repository, $fullSessID))) ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @param string $sessIDWithType
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function read($repository, $fullSessID)
    {
        $this->checkRepoNameAndSessID($repository, $fullSessID);

        return unserialize(
            file_get_contents($this->buidPathToFile($repository, $fullSessID))
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
    public function save($repository, $fullSessID, array $sessionData)
    {
        $this->checkRepoNameAndSessID($repository, $fullSessID);

        $fullPathToFile = $this->buidPathToFile($repository, $fullSessID);

        return (file_put_contents($fullPathToFile, serialize($sessionData))) ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $repository
     * @param string $sessIDWithType
     * @return bool
     */
    public function erase($repository, $fullSessID)
    {
        $this->checkRepoNameAndSessID($repository, $fullSessID);

        $fullPathToFile = $this->buidPathToFile($repository, $fullSessID);

        return (file_exists($fullPathToFile)) ? unlink($fullPathToFile) : false;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $sessionSettings
     * @param array $sessionTypes
     * @return void
     */
    public function collectGarbage(array $sessionSettings, array $sessionTypes)
    {
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
     * Determines the type of the current session
     * by the flag in the name of the file.
     *
     * @param string $filename      Filename.
     * @param array $sessionTypes   Session types.
     * @return string               Session type (if exist).
     */
    protected function getSessionType($filename, array $sessionTypes)
    {
        foreach ($sessionTypes as $type) {
            $pos = strrpos($filename, $type, -1);
            if ($pos) {
                return substr($filename, $pos);
            }
        }
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
