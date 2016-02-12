<?php

namespace SessionMuscle\Adapters;

/**
 * Interface ISessionAdapter.
 * @package SessionMuscle\Adapters
 */
interface ISessionAdapter
{
    /**
     * This method checks whether the session storage is available for
     * reading and writing. If the sessions repository is a database,
     * this method must check the connection with it, if the sessions
     * are stored in files this method must check folders permissions
     * to reading and writing such files. If successful, the method
     * must return true, on failure, the method must return false.
     *
     * @param string|resource $repository
     * @return bool
     */
    public function checkAccess($repository);

    /**
     * This method verifies the existence of a specific session by its
     * unique identifier. If this a database record - the existence of
     * such record, if this a file - the the existence of such file,
     * respectively.
     * This method takes two parameters. First, session repository.
     * Second, session unique identifier.
     * If successful, this method must return true, on failure, this
     * method must return false.
     *
     * @param string|resource $repository   Repository that contains
     *                                      session data.
     * @param string $sessIDWithType        The unique session ID with
     *                                      session type (e.g. database
     *                                      field or file name).
     * @return bool                         True on success, false on
     *                                      failure.
     */
    public function isExist($repository, $sessIDWithType);

    /**
     * This method reads data from session. On success return an
     * unserialized array. On failure return false.
     *
     * @param string|resource $repository   Repository that contains
     *                                      session data.
     * @param string $sessIDWithType        The unique session ID with session
     *                                      type (e.g. database field or file
     *                                      name). Where it is necessary to
     *                                      read the data.
     * @return array|false                  Unserialized array on success or
     *                                      false if failure.
     */
    public function read($repository, $sessIDWithType);

    /**
     * This method serialaize session data and stores them in
     * session repository.
     *
     * @param string|resource $repository   Repository where you want
     *                                      save the session data.
     * @param string $sessIDWithType        The unique session ID with session type
     *                                      (e.g. database field or file name).
     * @param array $sessionData            Current session data.
     * @return bool                         True on success, false on failure.
     */
    public function save($repository, $sessIDWithType, $sessionData);

    /**
     * This method must remove the entity in which the session
     * data are stored. For example, a database record or file.
     * Returns true on success, or false on failure.
     *
     * @param string|resource $repository   Repository that contains
     *                                      session data.
     * @param string $sessIDWithType        The unique session ID with
     *                                      session type (e.g. database
     *                                      field or file name). Where
     *                                      you want to remove.
     * @return bool                         True on success, false on
     *                                      failure.
     */
    public function erase($repository, $sessIDWithType);

    /**
     * @param $sessionSettings
     * @param $sessionTypes
     * @return mixed
     */
    public function collectGarbage($sessionSettings, $sessionTypes);
}
