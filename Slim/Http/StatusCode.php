<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Http;

class StatusCode
{
    const int HTTP_CONTINUE = 100;
    const int HTTP_SWITCHING_PROTOCOLS = 101;
    const int HTTP_PROCESSING = 102;

    const int HTTP_OK = 200;
    const int HTTP_CREATED = 201;
    const int HTTP_ACCEPTED = 202;
    const int HTTP_NONAUTHORITATIVE_INFORMATION = 203;
    const int HTTP_NO_CONTENT = 204;
    const int HTTP_RESET_CONTENT = 205;
    const int HTTP_PARTIAL_CONTENT = 206;
    const int HTTP_MULTI_STATUS = 207;
    const int HTTP_ALREADY_REPORTED = 208;
    const int HTTP_IM_USED = 226;

    const int HTTP_MULTIPLE_CHOICES = 300;
    const int HTTP_MOVED_PERMANENTLY = 301;
    const int HTTP_FOUND = 302;
    const int HTTP_SEE_OTHER = 303;
    const int HTTP_NOT_MODIFIED = 304;
    const int HTTP_USE_PROXY = 305;
    const int HTTP_UNUSED= 306;
    const int HTTP_TEMPORARY_REDIRECT = 307;
    const int HTTP_PERMANENT_REDIRECT = 308;

    const int HTTP_BAD_REQUEST = 400;
    const int HTTP_UNAUTHORIZED  = 401;
    const int HTTP_PAYMENT_REQUIRED = 402;
    const int HTTP_FORBIDDEN = 403;
    const int HTTP_NOT_FOUND = 404;
    const int HTTP_METHOD_NOT_ALLOWED = 405;
    const int HTTP_NOT_ACCEPTABLE = 406;
    const int HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const int HTTP_REQUEST_TIMEOUT = 408;
    const int HTTP_CONFLICT = 409;
    const int HTTP_GONE = 410;
    const int HTTP_LENGTH_REQUIRED = 411;
    const int HTTP_PRECONDITION_FAILED = 412;
    const int HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const int HTTP_REQUEST_URI_TOO_LONG = 414;
    const int HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const int HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const int HTTP_EXPECTATION_FAILED = 417;
    const int HTTP_IM_A_TEAPOT = 418;
    const int HTTP_MISDIRECTED_REQUEST = 421;
    const int HTTP_UNPROCESSABLE_ENTITY = 422;
    const int HTTP_LOCKED = 423;
    const int HTTP_FAILED_DEPENDENCY = 424;
    const int HTTP_UPGRADE_REQUIRED = 426;
    const int HTTP_PRECONDITION_REQUIRED = 428;
    const int HTTP_TOO_MANY_REQUESTS = 429;
    const int HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const int HTTP_CONNECTION_CLOSED_WITHOUT_RESPONSE = 444;
    const int HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const int HTTP_CLIENT_CLOSED_REQUEST = 499;

    const int HTTP_INTERNAL_SERVER_ERROR = 500;
    const int HTTP_NOT_IMPLEMENTED = 501;
    const int HTTP_BAD_GATEWAY = 502;
    const int HTTP_SERVICE_UNAVAILABLE = 503;
    const int HTTP_GATEWAY_TIMEOUT = 504;
    const int HTTP_VERSION_NOT_SUPPORTED = 505;
    const int HTTP_VARIANT_ALSO_NEGOTIATES = 506;
    const int HTTP_INSUFFICIENT_STORAGE = 507;
    const int HTTP_LOOP_DETECTED = 508;
    const int HTTP_NOT_EXTENDED = 510;
    const int HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;
    const int HTTP_NETWORK_CONNECTION_TIMEOUT_ERROR = 599;
}
