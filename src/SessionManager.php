<?php
declare(strict_types=1);
/**
 * Kooser Session - Securely manage and preserve session data.
 * 
 * @package Kooser\Session.
 */

namespace Kooser\Session;

/**
 * Secure session management.
 *
 * You can start a session using the static method `SessionManager::start(...)` which
 * is compatible to PHP's built-in `session_start()` function.
 *
 * @class SessionManager.
 */
final class SessionManager
{

    /** @var array $sessionConfig The session configuration. */
    private static $sessionConfig = [];

    /**
     * Starts or resumes a session in a way compatible to PHP's built-in `session_start()` function
     *
     * @param array $sessionConfig The session configuration.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-start.php>.
     */
    public static function start(array $sessionConfig = [
        'use_cookies' => \true,
        'use_fingerprint' => \true,
        'fingerprint_validators' => []
    ]): bool {
        self::$sessionConfig = $sessionConfig;
        $result = \session_start($sessionConfig);
        if (!isset($sessionConfig['use_fingerprint']) || $sessionConfig['use_fingerprint']) {
            if (!isset($sessionConfig['fingerprint_validators'])) {
               $sessionConfig['fingerprint_validators'] = [];
            }
            $fpManager = new FingerprintManager($sessionConfig['fingerprint_validators']);
            if (self::has("session_fingerprint")) {
                $fp = $fpManager->generate();
                if (!\hash_equals(self::get("session_fingerprint"), $fp)) {
                    self::destory();
                }
            } else {
                self::set("session_fingerprint", $fpManager->generate());
            }
            $fpManager->clear();
        }
        return (bool) $result;
    }

    /**
     * Sets user-level session storage functions.
     *
     * @param \SessionHandlerInterface $handler          The session handler to use.
     * @param bool                     $registerShutdown Should we use the register shutdown function.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-set-save-handler.php>.
     */
    public static function setSaveHandler(\SessionHandlerInterface $handler, bool $registerShutdown = \true): bool
    {
        return (bool) \session_set_save_handler($handler, $registerShutdown);
    }

    /**
     * Get and/or set the current session save path.
     *
     * @param string $path Session data path. If specified, the path to which data is saved will be changed.
     *                     session_save_path() needs to be called before session_start() for that purpose.
     *
     * @return string Returns the path of the current directory used for data storage.
     *
     * @see <https://www.php.net/manual/en/function.session-save-path.php>.
     */
    public static function setSavePath(string $path): string
    {
        return (string) \session_save_path($path);
    }

    /**
     * Get or set the ID of the current session.
     *
     * @param string The session ID to create.
     *
     * @return string Returns the session ID or an empty string.
     *
     * @see <https://www.php.net/manual/en/function.session-id.php>.
     */
    public static function id(string $id = ""): string
    {
        if ($id == "") {
            return (string) \session_id();
        }
        // @codeCoverageIgnoreStart
        return (string) \session_id($id);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Destroys all data registered to a session.
     *
     * @param bool $deleteCookie Should we delete the cookie header.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-destroy.php>.
     */
    public static function destroy(bool $deleteCookie = \true): bool
    {
        $_SESSION = array();
        // @codeCoverageIgnoreStart
        if (self::$sessionConfig["use_cookies"]) {
            $params = \session_get_cookie_params();
            \setcookie(\session_name(), '', \time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        // @codeCoverageIgnoreEnd
        return (bool) \session_destroy();
    }

    /**
     * Perform session data garbage collection.
     *
     * @return void Returns nothing.
     *
     * @see <https://www.php.net/manual/en/function.session-gc.php>.
     */
    public static function gc(): void
    {
        \session_gc();
    }

    /**
     * Abort the session and discard any changes.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-abort.php>.
     */
    public static function abort(): bool
    {
        return (bool) \session_abort();
    }

    /**
     * Check to see if a session already exists.
     *
     * @return bool Returns TRUE if one exists and FALSE if not.
     *
     * @see <https://www.php.net/manual/en/function.session-status.php>.
     */
    public static function exists(): bool
    {
        // @codeCoverageIgnoreStart
        if (\php_sapi_name() !== 'cli') {
            return (bool) (\session_status() === \PHP_SESSION_ACTIVE) ? \true : \false;
        }
        return (bool) \false;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Write session data and end session.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-write-close.php>.
     */
    public static function commit(): bool
    {
        return (bool) \session_write_close();
    }

    /**
     * Re-initialize session array with original values.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-reset.php>.
     */
    public static function reset(): bool
    {
        return (bool) \session_reset();
    }

    /**
     * Re-generates the session ID in a way compatible to PHP's built-in
     * `session_regenerate_id()` function.
     *
     * @param bool $deleteOldSession Whether to delete the old session or not.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     *
     * @see <https://www.php.net/manual/en/function.session-regenerate-id.php>.
     */
    public static function regenerate(bool $deleteOldSession = \true): bool {
        return (bool) \session_regenerate_id($deleteOldSession);
    }

    /**
     * Checks whether a value for the specified key exists in the session.
     *
     * @param string $key The key to check.
     *
     * @return bool Returns TRUE if the key was found and FALSE if not.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Returns the requested value from the session or, if not found, the
     * specified default value.
     *
     * @param string $key          The key to retrieve the value for.
     * @param mixed  $defaultValue The default value to return if the
     *                             requested value cannot be found.
     *
     * @return mixed Returns the requested value or the default
     *               value.
     */
    public static function get(string $key, $defaultValue = \null)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return $defaultValue;
    }

    /**
     * Returns the requested value and removes it from the session.
     *
     * This is identical to calling `get` first and then `remove` for the same
     * key.
     *
     * @param string $key         The key to retrieve and remove the value for.
     * @param mixed $defaultValue The default value to return if the requested
     *                            value cannot be found.
     *
     * @return mixed The requested value or the default value.
     */
    public static function flash(string $key, $defaultValue = \null)
    {
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $value;
        }
        return $defaultValue;
    }

    /**
     * Sets the value for the specified key to the given value.
     *
     * Any data that already exists for the specified key will be overwritten.
     *
     * @param string $key   The key to set the value for.
     * @param mixed  $value The value to set.
     *
     * @return void Returns nothing.
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Removes the value for the specified key from the session.
     *
     * @param string $key The key to remove the value for.
     *
     * @return void Returns nothing.
     */
    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
