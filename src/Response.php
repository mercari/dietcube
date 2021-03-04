<?php
/**
 * This class is inspired by PSR-7 HTTP Message but not complied with it.
 * Because of the balance between the design and reallity,
 * to handle HTTP headers and body simply are only required by this class.
 */

namespace Dietcube;

use Dietcube\Components\LoggerAwareTrait;

class Response
{
    use LoggerAwareTrait;

    /**
     * @var array<int, string>
     * @const array Map of standard HTTP status code/reason phrases
     */
    public const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /** @var null|string */
    protected $reason_phrase = '';

    /** @var int */
    protected $status_code = 200;

    /** @var null|string */
    protected $body;

    protected $headers = [];

    /** @var string  */
    protected $version;

    public function __construct($status_code = 200, $headers = [], $body = null, $version = "1.1")
    {
        $this->status_code = $status_code;
        $this->headers = $headers;
        $this->body = $body;
        $this->version = $version;
    }

    /**
     * @param int|string $status_code
     * @return $this
     */
    public function setStatusCode($status_code): self
    {
        if (null === self::PHRASES[$this->status_code]) {
            throw new \InvalidArgumentException("Invalid status code '{$this->status_code}'");
        }

        $this->status_code = $status_code;
        $this->setReasonPhrase();

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    /**
     * @return $this
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed $this
     * @throws \InvalidArgumentException
     */
    public function setReasonPhrase($phrase = null): self
    {
        if ($phrase !== null) {
            $this->reason_phrase = $phrase;
            return $this;
        }

        if (null !== self::PHRASES[$this->status_code]) {
            $this->reason_phrase = self::PHRASES[$this->status_code];
            return $this;
        }

        throw new \InvalidArgumentException("Invalid status code '{$this->status_code}'");
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): ?string
    {
        return $this->reason_phrase;
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    public function sendBody(): void
    {
        if ($this->body !== null) {
            echo $this->body;
        }
    }

    public function sendHeaders(): self
    {
        if (headers_sent()) {
            $this->logger || $this->logger->error('Header already sent.');
            return $this;
        }

        $this->sendHttpHeader();
        foreach ($this->headers as $name => $value) {
            $v = implode(',', $value);
            header("{$name}: {$v}", true);
        }
    }

    public function sendHttpHeader(): void
    {
        header("HTTP/{$this->version} {$this->status_code} {$this->reason_phrase}", true);
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }

        return $this;
    }

    /**
     * @param $header
     * @param $value
     * @return $this
     */
    public function setHeader($header, $value): self
    {
        $header = trim($header);
        if (!is_array($value)) {
            $value = trim($value);
            $this->headers[$header][] = $value;
        } else {
            foreach ($value as $v) {
                $v = trim($v);
                $this->headers[$header][] = $v;
            }
        }

        return $this;
    }
}
