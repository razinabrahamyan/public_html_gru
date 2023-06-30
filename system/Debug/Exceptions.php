<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Debug;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use Config\Exceptions as ExceptionsConfig;
use Config\Paths;
use ErrorException;
use Psr\Log\LogLevel;
use Throwable;

/**
 * Exceptions manager
 */
class Exceptions
{
    use ResponseTrait;

    /**
     * Nesting level of the output buffering mechanism
     *
     * @var int
     */
    public $ob_level;

    /**
     * The path to the directory containing the
     * cli and html error view directories.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Config for debug exceptions.
     *
     * @var ExceptionsConfig
     */
    protected $config;

    /**
     * The request.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * The outgoing response.
     *
     * @var Response
     */
    protected $response;

    /**
     * @param CLIRequest|IncomingRequest $request
     */
    public function __construct(ExceptionsConfig $config, $request, Response $response)
    {
        $this->ob_level = ob_get_level();
        $this->viewPath = rtrim($config->errorViewPath, '\\/ ') . DIRECTORY_SEPARATOR;
        $this->config   = $config;
        $this->request  = $request;
        $this->response = $response;

        // workaround for upgraded users
        if (! isset($this->config->sensitiveDataInTrace)) {
            $this->config->sensitiveDataInTrace = [];
        }
    }

    /**
     * Responsible for registering the error, exception and shutdown
     * handling of our application.
     *
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    /**
     * Catches any uncaught errors and exceptions, including most Fatal errors
     * (Yay PHP7!). Will log the error, display it if display_errors is on,
     * and fire an event that allows custom actions to be taken at this point.
     *
     * @codeCoverageIgnore
     */
    public function exceptionHandler(Throwable $exception)
    {
        [$statusCode, $exitCode] = $this->determineCodes($exception);

        if ($this->config->log === true && ! in_array($statusCode, $this->config->ignoreCodes, true)) {
            log_message('critical', "{message}\nin {exFile} on line {exLine}.\n{trace}", [
                'message' => $exception->getMessage(),
                'exFile'  => clean_path($exception->getFile()), // {file} refers to THIS file
                'exLine'  => $exception->getLine(), // {line} refers to THIS line
                'trace'   => self::renderBacktrace($exception->getTrace()),
            ]);
        }

        if (! is_cli()) {
            try {
                $this->response->setStatusCode($statusCode);
            } catch (HTTPException $e) {
                // Workaround for invalid HTTP status code.
                $statusCode = 500;
                $this->response->setStatusCode($statusCode);
            }

            if (! headers_sent()) {
                header(sprintf('HTTP/%s %s %s', $this->request->getProtocolVersion(), $this->response->getStatusCode(), $this->response->getReasonPhrase()), true, $statusCode);
            }

            if (strpos($this->request->getHeaderLine('accept'), 'text/html') === false) {
                $this->respond(ENVIRONMENT === 'development' ? $this->collectVars($exception, $statusCode) : '', $statusCode)->send();

                exit($exitCode);
            }
        }

        $this->render($exception, $statusCode);

        exit($exitCode);
    }

    /**
     * The callback to be registered to `set_error_handler()`.
     *
     * @return bool
     *
     * @throws ErrorException
     *
     * @codeCoverageIgnore
     */
    public function errorHandler(int $severity, string $message, ?string $file = null, ?int $line = null)
    {
        if (error_reporting() & $severity) {
            // @TODO Remove if Faker is fixed.
            if ($this->isFakerDeprecationError($severity, $message, $file, $line)) {
                // Ignore the error.
                return true;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        return false; // return false to propagate the error to PHP standard error handler
    }

    /**
     * Workaround for Faker deprecation errors in PHP 8.2.
     *
     * @see https://github.com/FakerPHP/Faker/issues/479
     */
    private function isFakerDeprecationError(int $severity, string $message, ?string $file = null, ?int $line = null)
    {
        if (
            $severity === E_DEPRECATED
            && strpos($file, VENDORPATH . 'fakerphp/faker/') !== false
            && $message === 'Use of "static" in callables is deprecated'
        ) {
            log_message(
                LogLevel::WARNING,
                '[DEPRECATED] {message} in {errFile} on line {errLine}.',
                [
                    'message' => $message,
                    'errFile' => clean_path($file ?? ''),
                    'errLine' => $line ?? 0,
                ]
            );

            return true;
        }

        return false;
    }

    /**
     * Checks to see if any errors have happened during shutdown that
     * need to be caught and handle them.
     *
     * @codeCoverageIgnore
     */
    public function shutdownHandler()
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        ['type' => $type, 'message' => $message, 'file' => $file, 'line' => $line] = $error;

        if (in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $this->exceptionHandler(new ErrorException($message, 0, $type, $file, $line));
        }
    }

    /**
     * Determines the view to display based on the exception thrown,
     * whether an HTTP or CLI request, etc.
     *
     * @return string The path and filename of the view file to use
     */
    protected function determineView(Throwable $exception, string $templatePath): string
    {
        // Production environments should have a custom exception file.
        $view         = 'production.php';
        $templatePath = rtrim($templatePath, '\\/ ') . DIRECTORY_SEPARATOR;

        if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors'))) {
            $view = 'error_exception.php';
        }

        // 404 Errors
        if ($exception instanceof PageNotFoundException) {
            return 'error_404.php';
        }

        // Allow for custom views based upon the status code
        if (is_file($templatePath . 'error_' . $exception->getCode() . '.php')) {
            return 'error_' . $exception->getCode() . '.php';
        }

        return $view;
    }

    /**
     * Given an exception and status code will display the error to the client.
     */
    protected function render(Throwable $exception, int $statusCode)
    {
        // Determine possible directories of error views
        $path    = $this->viewPath;
        $altPath = rtrim((new Paths())->viewDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR;

        $path    .= (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;
        $altPath .= (is_cli() ? 'cli' : 'html') . DIRECTORY_SEPARATOR;

        // Determine the views
        $view    = $this->determineView($exception, $path);
        $altView = $this->determineView($exception, $altPath);

        // Check if the view exists
        if (is_file($path . $view)) {
            $viewFile = $path . $view;
        } elseif (is_file($altPath . $altView)) {
            $viewFile = $altPath . $altView;
        }

        if (! isset($viewFile)) {
            echo 'The error view files were not found. Cannot render exception trace.';

            exit(1);
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_clean();
        }

        echo(function () use ($exception, $statusCode, $viewFile): string {
            $vars = $this->collectVars($exception, $statusCode);
            extract($vars, EXTR_SKIP);

            ob_start();
            include $viewFile;

            return ob_get_clean();
        })();
    }

    /**
     * Gathers the variables that will be made available to the view.
     */
    protected function collectVars(Throwable $exception, int $statusCode): array
    {
        $trace = $exception->getTrace();

        if ($this->config->sensitiveDataInTrace !== []) {
            $this->maskSensitiveData($trace, $this->config->sensitiveDataInTrace);
        }

        $return = [
            'title'   => get_class($exception),
            'type'    => get_class($exception),
            'code'    => $statusCode,
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $trace,
        ];

        //составляем сообщение для телеграм
        $this->send_telegram_error($return);

        return $return;
    }

    /*
    Сообщение в личку об отладке
     */
    public function send_telegram_error($data)  {

        if ($data['code'] == 404) {
            return FALSE;
        }

        $config = config('App');
        if ($config->debug_chat_id == 0) {
            return FALSE;
        }

        //получаем токен бота из БД
        $this->db = \Config\Database::connect();
        $this->encryption = \Config\Services::encrypter();
        $value = $this->db->table('settings')->where('name', 'api_key')->get(1)->getRow()->value;
        if (empty($value)) {
            return FALSE;
        }
        $this->telegram_bot_api_key = $this->encryption->decrypt(base64_decode($value));

        if (empty($this->telegram_bot_api_key)) {
            return FALSE;
        }

        $text = "<strong>".$data['title']." (".$data['code'].")</strong>: ";
        $text .= $data['message'];
        $text .= "\n\n";
        $text .= "<code>" . $data['file'] . "</code>";
        $text .= " <i>(" . $data['line'] . ")</i>";

        if ($data['type'] == "mysqli_sql_exception") {
            
            $i = 0;
            $query = "";
            foreach ($data['trace'] as $trace) {
                if (!isset($trace['args'])) {
                    continue;
                }
                foreach ($trace['args'] as $arg) {
                    if ($i > 0) {
                        continue;
                    }
                    $query .= "<code>";
                    $query .= $arg;
                    $query .= "</code>";
                    $i++;
                }
            }
            if ($i > 0) {
                $text .= "\n\nВ запросе:\n";
                $text .= $query;
            }
        }
        
        $params['disable_web_page_preview'] = TRUE;
        $params['parse_mode'] = 'html';
        $params['chat_id'] = $config->debug_chat_id;
        $params['text'] = $text;

        //отправляем в телеграм
        $url = 'https://api.telegram.org/bot' . $this->telegram_bot_api_key . '/sendMessage?' . http_build_query($params);
        $data = array('url' => base_url('telegram/hook'));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($ch);
        curl_close($ch);

        return TRUE;
    }

    /**
     * Mask sensitive data in the trace.
     *
     * @param array|object $trace
     */
    protected function maskSensitiveData(&$trace, array $keysToMask, string $path = '')
    {
        foreach ($keysToMask as $keyToMask) {
            $explode = explode('/', $keyToMask);
            $index   = end($explode);

            if (strpos(strrev($path . '/' . $index), strrev($keyToMask)) === 0) {
                if (is_array($trace) && array_key_exists($index, $trace)) {
                    $trace[$index] = '******************';
                } elseif (is_object($trace) && property_exists($trace, $index) && isset($trace->{$index})) {
                    $trace->{$index} = '******************';
                }
            }
        }

        if (is_object($trace)) {
            $trace = get_object_vars($trace);
        }

        if (is_array($trace)) {
            foreach ($trace as $pathKey => $subarray) {
                $this->maskSensitiveData($subarray, $keysToMask, $path . '/' . $pathKey);
            }
        }
    }

    /**
     * Determines the HTTP status code and the exit status code for this request.
     */
    protected function determineCodes(Throwable $exception): array
    {
        $statusCode = abs($exception->getCode());

        if ($statusCode < 100 || $statusCode > 599) {
            $exitStatus = $statusCode + EXIT__AUTO_MIN;

            if ($exitStatus > EXIT__AUTO_MAX) {
                $exitStatus = EXIT_ERROR;
            }

            $statusCode = 500;
        } else {
            $exitStatus = EXIT_ERROR;
        }

        return [$statusCode, $exitStatus];
    }

    // --------------------------------------------------------------------
    // Display Methods
    // --------------------------------------------------------------------

    /**
     * This makes nicer looking paths for the error output.
     *
     * @deprecated Use dedicated `clean_path()` function.
     */
    public static function cleanPath(string $file): string
    {
        switch (true) {
            case strpos($file, APPPATH) === 0:
                $file = 'APPPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(APPPATH));
                break;

            case strpos($file, SYSTEMPATH) === 0:
                $file = 'SYSTEMPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(SYSTEMPATH));
                break;

            case strpos($file, FCPATH) === 0:
                $file = 'FCPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(FCPATH));
                break;

            case defined('VENDORPATH') && strpos($file, VENDORPATH) === 0:
                $file = 'VENDORPATH' . DIRECTORY_SEPARATOR . substr($file, strlen(VENDORPATH));
                break;
        }

        return $file;
    }

    /**
     * Describes memory usage in real-world units. Intended for use
     * with memory_get_usage, etc.
     */
    public static function describeMemory(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024, 2) . 'KB';
        }

        return round($bytes / 1_048_576, 2) . 'MB';
    }

    /**
     * Creates a syntax-highlighted version of a PHP file.
     *
     * @return bool|string
     */
    public static function highlightFile(string $file, int $lineNumber, int $lines = 15)
    {
        if (empty($file) || ! is_readable($file)) {
            return false;
        }

        // Set our highlight colors:
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#767a7e; font-style: italic');
            ini_set('highlight.default', '#c7c7c7');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#f1ce61;');
            ini_set('highlight.string', '#869d6a');
        }

        try {
            $source = file_get_contents($file);
        } catch (Throwable $e) {
            return false;
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $source = str_replace('<br />', "\n", $source[1]);
        $source = explode("\n", str_replace("\r\n", "\n", $source));

        // Get just the part to show
        $start = max($lineNumber - (int) round($lines / 2), 0);

        // Get just the lines we need to display, while keeping line numbers...
        $source = array_splice($source, $start, $lines, true);

        // Used to format the line number in the source
        $format = '% ' . strlen((string) ($start + $lines)) . 'd';

        $out = '';
        // Because the highlighting may have an uneven number
        // of open and close span tags on one line, we need
        // to ensure we can close them all to get the lines
        // showing correctly.
        $spans = 1;

        foreach ($source as $n => $row) {
            $spans += substr_count($row, '<span') - substr_count($row, '</span');
            $row = str_replace(["\r", "\n"], ['', ''], $row);

            if (($n + $start + 1) === $lineNumber) {
                preg_match_all('#<[^>]+>#', $row, $tags);

                $out .= sprintf(
                    "<span class='line highlight'><span class='number'>{$format}</span> %s\n</span>%s",
                    $n + $start + 1,
                    strip_tags($row),
                    implode('', $tags[0])
                );
            } else {
                $out .= sprintf('<span class="line"><span class="number">' . $format . '</span> %s', $n + $start + 1, $row) . "\n";
            }
        }

        if ($spans > 0) {
            $out .= str_repeat('</span>', $spans);
        }

        return '<pre><code>' . $out . '</code></pre>';
    }

    private static function renderBacktrace(array $backtrace): string
    {
        $backtraces = [];

        foreach ($backtrace as $index => $trace) {
            $frame = $trace + ['file' => '[internal function]', 'line' => '', 'class' => '', 'type' => '', 'args' => []];

            if ($frame['file'] !== '[internal function]') {
                $frame['file'] = sprintf('%s(%s)', $frame['file'], $frame['line']);
            }

            unset($frame['line']);
            $idx = $index;
            $idx = str_pad((string) ++$idx, 2, ' ', STR_PAD_LEFT);

            $args = implode(', ', array_map(static function ($value): string {
                switch (true) {
                    case is_object($value):
                        return sprintf('Object(%s)', get_class($value));

                    case is_array($value):
                        return $value !== [] ? '[...]' : '[]';

                    case $value === null:
                        return 'null';

                    case is_resource($value):
                        return sprintf('resource (%s)', get_resource_type($value));

                    case is_string($value):
                        return var_export(clean_path($value), true);

                    default:
                        return var_export($value, true);
                }
            }, $frame['args']));

            $backtraces[] = sprintf(
                '%s %s: %s%s%s(%s)',
                $idx,
                clean_path($frame['file']),
                $frame['class'],
                $frame['type'],
                $frame['function'],
                $args
            );
        }

        return implode("\n", $backtraces);
    }
}